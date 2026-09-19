@php
    $flashAlerts = [];

    if (session('success')) {
        $flashAlerts[] = ['type' => 'success', 'title' => __('Success'), 'message' => session('success')];
    }

    if (session('warning')) {
        $flashAlerts[] = ['type' => 'warning', 'title' => __('Notice'), 'message' => session('warning')];
    }

    if ($errors->isNotEmpty()) {
        $flashAlerts[] = [
            'type' => 'error',
            'title' => __('Failed'),
            'message' => implode("\n", $errors->all()),
        ];
    }
@endphp
<script>
    (function () {
        var brand = '#188ae2';

        function swalAvailable() {
            return typeof Swal === 'function';
        }

        function fire(options) {
            if (!swalAvailable()) {
                if (options.showCancelButton) {
                    return Promise.resolve({ value: window.confirm(options.text || options.title) });
                }

                window.alert(options.text || options.title);

                return Promise.resolve({ value: true });
            }

            return Swal.fire(Object.assign({
                confirmButtonText: @json(__('OK')),
                confirmButtonColor: brand,
                buttonsStyling: true,
            }, options));
        }

        window.showAppMessage = function (type, title, message) {
            var kind = type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'error');

            return fire({
                type: kind,
                title: title,
                text: message,
            });
        };

        window.showToast = function (type, message) {
            var titles = {
                success: @json(__('Success')),
                warning: @json(__('Notice')),
                danger: @json(__('Failed')),
                error: @json(__('Failed'))
            };

            return window.showAppMessage(
                type === 'success' ? 'success' : (type === 'warning' ? 'warning' : 'error'),
                titles[type] || @json(__('Notice')),
                message
            );
        };

        window.confirmAppAction = function (message, title) {
            return fire({
                type: 'question',
                title: title || @json(__('Confirm')),
                text: message,
                showCancelButton: true,
                confirmButtonText: @json(__('OK')),
                cancelButtonText: @json(__('Cancel')),
            }).then(function (result) {
                return Boolean(result.value);
            });
        };

        document.addEventListener('submit', function (event) {
            var form = event.target;
            var submitter = event.submitter;
            var message = (submitter && submitter.getAttribute('data-confirm'))
                || (form instanceof HTMLFormElement ? form.getAttribute('data-confirm') : null);

            if (!(form instanceof HTMLFormElement) || ! message) {
                return;
            }

            if (form.dataset.appConfirmed === '1') {
                return;
            }

            event.preventDefault();

            window.confirmAppAction(message).then(function (ok) {
                if (!ok) {
                    return;
                }

                form.dataset.appConfirmed = '1';

                if (typeof form.requestSubmit === 'function') {
                    form.requestSubmit(submitter || undefined);
                } else {
                    if (submitter && submitter.name) {
                        var hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = submitter.name;
                        hidden.value = submitter.value;
                        form.appendChild(hidden);
                    }
                    form.submit();
                }
            });
        });

        @foreach ($flashAlerts as $alert)
        window.showAppMessage(@json($alert['type']), @json($alert['title']), @json($alert['message']));
        @endforeach
    })();
</script>
