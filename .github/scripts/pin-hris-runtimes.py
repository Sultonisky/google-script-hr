#!/usr/bin/env python3
"""Pin hris-app to PHP 8.4.27 and Node 24.22.1 before the image build.

The VPS Dockerfile installs Node from NodeSource (setup_22.x), not from a
node:<version> image. Rewriting only image tags leaves Node 22 in place.
"""

import json
import os
import re
import sys

PHP_VERSION = "8.4.27"
NODE_VERSION = "24.22.1"
COMPOSE_CONFIG = "/tmp/hris-compose.json"

NODE_INSTALL = """# ============================================================
# Node.js 24.22.1 + npm
# ============================================================

ARG NODE_VERSION=24.22.1
RUN arch="$(dpkg --print-architecture)" \\
    && case "$arch" in \\
         amd64) node_arch="x64" ;; \\
         arm64) node_arch="arm64" ;; \\
         *) echo "Unsupported architecture: $arch" >&2; exit 1 ;; \\
       esac \\
    && curl -fsSL "https://nodejs.org/dist/v${NODE_VERSION}/node-v${NODE_VERSION}-linux-${node_arch}.tar.gz" -o /tmp/node.tar.gz \\
    && tar -xzf /tmp/node.tar.gz -C /usr/local --strip-components=1 \\
    && rm -f /tmp/node.tar.gz \\
    && node --version \\
    && npm --version
"""

NODESOURCE_BLOCK = re.compile(
    "(?:# =+\r?\n# Node\\.js [^\r\n]*\r?\n# =+\r?\n\r?\n)?"
    "RUN curl -fsSL https://deb\\.nodesource\\.com/setup_\\d+\\.x \\| bash - \\\\\r?\n"
    "(?:[ \t]+&&[^\r\n]*\r?\n)*"
)
PHP_IMAGE_RE = re.compile(r"php:(\d+\.\d+(?:\.\d+)?)([^\s'\"]*)")
NODE_IMAGE_RE = re.compile(r"node:(\d+(?:\.\d+){0,2})([^\s'\"]*)")
PHP_ARG_RE = re.compile(
    r"(?m)^(\s*ARG\s+[A-Z0-9_]*PHP[A-Z0-9_]*=)['\"]?(\d+\.\d+(?:\.\d+)?)['\"]?"
)
NODE_ARG_RE = re.compile(
    r"(?m)^(\s*ARG\s+[A-Z0-9_]*NODE[A-Z0-9_]*=)['\"]?(\d+(?:\.\d+){0,2})['\"]?"
)
PHP_COMPOSE_RE = re.compile(r"(PHP_VERSION\s*[:=]\s*['\"]?)(\d+\.\d+(?:\.\d+)?)")
NODE_COMPOSE_RE = re.compile(r"(NODE_VERSION\s*[:=]\s*['\"]?)(\d+(?:\.\d+){0,2})")


def _strip_digest(suffix):
    return re.sub(r"@sha256:[0-9a-fA-F]+", "", suffix or "")


def _pin_php_token(match):
    return f"php:{PHP_VERSION}{_strip_digest(match.group(2))}"


def _pin_node_token(match):
    return f"node:{NODE_VERSION}{_strip_digest(match.group(2))}"


def transform(original):
    updated = NODESOURCE_BLOCK.sub(NODE_INSTALL, original, count=1)
    updated = PHP_IMAGE_RE.sub(_pin_php_token, updated)
    updated = NODE_IMAGE_RE.sub(_pin_node_token, updated)
    updated = PHP_ARG_RE.sub(rf"\g<1>{PHP_VERSION}", updated)
    updated = NODE_ARG_RE.sub(rf"\g<1>{NODE_VERSION}", updated)
    updated = PHP_COMPOSE_RE.sub(rf"\g<1>{PHP_VERSION}", updated)
    updated = NODE_COMPOSE_RE.sub(rf"\g<1>{NODE_VERSION}", updated)
    return updated


def _php_pinned(text):
    if f"php:{PHP_VERSION}" in text:
        return True
    return re.search(
        rf"PHP_VERSION\s*[:=]\s*['\"]?{re.escape(PHP_VERSION)}\b",
        text,
    ) is not None


def _node_pinned(text):
    if f"node-v{NODE_VERSION}-linux-" in text:
        return True
    if (
        f"ARG NODE_VERSION={NODE_VERSION}" in text
        and "node-v${NODE_VERSION}-linux-" in text
    ):
        return True
    return re.search(rf"node:{re.escape(NODE_VERSION)}(?!\d)", text) is not None


def assert_dockerfile_pinned(path, text):
    if not _php_pinned(text):
        sys.exit(f"No PHP image tag found in {path}")
    if "deb.nodesource.com/setup_" in text:
        sys.exit(f"NodeSource setup is still present in {path}")
    if not _node_pinned(text):
        sys.exit(
            f"Cannot pin Node {NODE_VERSION} in {path}. "
            "Expected a node:<version> image or the NodeSource setup_NN.x block."
        )


def pin_path(path, dockerfile):
    with open(path, encoding="utf-8") as handle:
        original = handle.read()
    updated = transform(original)
    if dockerfile:
        assert_dockerfile_pinned(path, updated)
    if updated == original:
        print(f"PHP {PHP_VERSION} and Node {NODE_VERSION} already set in {path}")
        return
    try:
        with open(path, "w", encoding="utf-8", newline="\n") as handle:
            handle.write(updated)
    except PermissionError:
        sys.exit(
            f"Cannot write {path}. The deploy user needs passwordless sudo "
            "or write access to the Docker files."
        )
    print(f"Pinned PHP {PHP_VERSION} and Node {NODE_VERSION} in {path}")


def resolve_dockerfile(root, config):
    service = config.get("services", {}).get("hris-app")
    build = service.get("build") if isinstance(service, dict) else None
    if not build:
        sys.exit("hris-app has no build section; refusing to deploy the current image")

    if isinstance(build, str):
        context, dockerfile = build, "Dockerfile"
    else:
        context = build.get("context") or root
        dockerfile = build.get("dockerfile") or "Dockerfile"

    if not os.path.isabs(context):
        context = os.path.abspath(os.path.join(root, context))
    if not os.path.isabs(dockerfile):
        dockerfile = os.path.join(context, dockerfile)
    return dockerfile


def main():
    if len(sys.argv) == 2 and sys.argv[1] == "--self-test":
        run_self_test()
        return
    if len(sys.argv) != 2 or not sys.argv[1]:
        sys.exit("Compose directory argument is required")

    root = sys.argv[1]
    if not os.path.isfile(COMPOSE_CONFIG):
        sys.exit(f"Compose config not found: {COMPOSE_CONFIG}")
    with open(COMPOSE_CONFIG, encoding="utf-8") as handle:
        config = json.load(handle)

    dockerfile = resolve_dockerfile(root, config)
    if not os.path.isfile(dockerfile):
        sys.exit(f"Dockerfile not found: {dockerfile}")

    pin_path(dockerfile, dockerfile=True)
    for name in ("docker-compose.yml", "docker-compose.yaml", "compose.yml", "compose.yaml"):
        path = os.path.join(root, name)
        if os.path.isfile(path) and os.path.abspath(path) != os.path.abspath(dockerfile):
            pin_path(path, dockerfile=False)


VPS_DOCKERFILE = """FROM php:8.4.27-fpm-bookworm

# ============================================================
# System dependencies + PHP extensions
# ============================================================

RUN apt-get update && apt-get install -y \\
    git \\
    unzip \\
    curl \\
    ca-certificates \\
    libpq-dev \\
    && docker-php-ext-install -j"$(nproc)" \\
        pdo_pgsql \\
        opcache \\
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# ============================================================
# Node.js 22 + npm
# ============================================================

RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \\
    && apt-get update \\
    && apt-get install -y nodejs \\
    && node --version \\
    && npm --version \\
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY app/mito-hris-laravel/ .

RUN composer install \\
    --no-dev \\
    --no-interaction \\
    --prefer-dist \\
    --optimize-autoloader

RUN npm install \\
    && npm run build \\
    && rm -rf node_modules

EXPOSE 9000

CMD ["php-fpm"]
"""


def run_self_test():
    pinned = transform(VPS_DOCKERFILE)
    assert_dockerfile_pinned("Dockerfile", pinned)
    if pinned == VPS_DOCKERFILE:
        sys.exit("expected the NodeSource block to change")
    if transform(pinned) != pinned:
        sys.exit("pin is not idempotent")
    if "setup_22.x" in pinned or "deb.nodesource.com" in pinned:
        sys.exit("NodeSource install survived")
    if "FROM php:8.4.27-fpm-bookworm\n" not in pinned:
        sys.exit("PHP tag was rewritten incorrectly")
    if "COPY --from=composer:2 /usr/bin/composer /usr/bin/composer" not in pinned:
        sys.exit("composer image line was rewritten")
    if "COPY app/mito-hris-laravel/ ." not in pinned:
        sys.exit("application copy line was dropped")
    if 'CMD ["php-fpm"]' not in pinned:
        sys.exit("php-fpm command was dropped")
    if "ARG NODE_VERSION=24.22.1\n" not in pinned:
        sys.exit("Node ARG was not pinned")
    expected_url = (
        "https://nodejs.org/dist/v${NODE_VERSION}/"
        "node-v${NODE_VERSION}-linux-${node_arch}.tar.gz"
    )
    if expected_url not in pinned:
        sys.exit("Node tarball URL was corrupted:\n" + pinned)
    if " \\\n" not in pinned:
        sys.exit("Dockerfile continuations were lost")

    upgraded = transform("FROM php:8.3-fpm-bookworm\nFROM node:22-alpine\n")
    if "FROM php:8.4.27-fpm-bookworm\n" not in upgraded:
        sys.exit(f"minor PHP tag was not upgraded: {upgraded}")
    if "FROM node:24.22.1-alpine\n" not in upgraded:
        sys.exit(f"node image tag was not upgraded: {upgraded}")

    digested = transform("FROM php:8.4.16-fpm-bookworm@sha256:0123456789abcdef\n")
    if digested != "FROM php:8.4.27-fpm-bookworm\n":
        sys.exit(f"image digest was not stripped: {digested}")

    print("self-test ok")


if __name__ == "__main__":
    main()
