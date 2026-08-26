@if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof showToast === 'function') {
                showToast('{{ session('success') }}', 'success');
            }
        });
    </script>
@endif

@if(session('error'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof showToast === 'function') {
                showToast('{{ session('error') }}', 'error');
            }
        });
    </script>
@endif

@if(session('warning'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof showToast === 'function') {
                showToast('{{ session('warning') }}', 'warning');
            }
        });
    </script>
@endif

@if(session('info'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof showToast === 'function') {
                showToast('{{ session('info') }}', 'info');
            }
        });
    </script>
@endif