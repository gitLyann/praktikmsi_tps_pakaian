<script>
    (function () {
        var STORAGE_KEY = 'lm-theme';
        var root = document.documentElement;

        function storedMode() {
            try {
                return localStorage.getItem(STORAGE_KEY);
            } catch (error) {
                return null;
            }
        }

        function paint(mode) {
            var dark = mode === 'dark';

            root.classList.toggle('dark', dark);
            root.classList.toggle('lm-dark', dark);
            root.setAttribute('data-bs-theme', dark ? 'dark' : 'light');

            document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
                button.setAttribute('aria-pressed', dark ? 'true' : 'false');
                button.setAttribute('aria-label', dark ? 'Aktifkan mode terang' : 'Aktifkan mode gelap');
                button.setAttribute('title', dark ? 'Mode terang' : 'Mode gelap');

                var icon = button.querySelector('[data-theme-icon]');
                if (icon) {
                    if (icon.classList.contains('bi')) {
                        icon.classList.toggle('bi-moon-stars', !dark);
                        icon.classList.toggle('bi-sun', dark);
                    } else {
                        icon.textContent = dark ? '\u2600' : '\u263E';
                    }
                }
            });

            document.dispatchEvent(new CustomEvent('lm:themechange', { detail: { dark: dark } }));
        }

        window.lmTheme = {
            current: function () {
                return root.classList.contains('dark') ? 'dark' : 'light';
            },
            set: function (mode) {
                try {
                    localStorage.setItem(STORAGE_KEY, mode === 'dark' ? 'dark' : 'light');
                } catch (error) {
                    // localStorage bisa diblokir; tema tetap berlaku untuk sesi ini.
                }

                paint(mode);
            },
            toggle: function () {
                window.lmTheme.set(window.lmTheme.current() === 'dark' ? 'light' : 'dark');
            }
        };

        paint(storedMode() === 'dark' ? 'dark' : 'light');

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-theme-toggle]').forEach(function (button) {
                button.addEventListener('click', function () {
                    window.lmTheme.toggle();
                });
            });
        });
    })();
</script>
