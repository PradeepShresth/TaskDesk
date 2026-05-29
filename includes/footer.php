<?php
/**
 * Shared page footer for authenticated views.
 * Closes the <main> opened in header.php, then renders the footer
 * bar and closes the document.
 */
?>
    </main>

    <footer class="site-footer">
        <div class="container">
            <small>
                &copy; <?= date('Y') ?> <?= e(APP_NAME) ?> &middot;
                MIT122 Interactive Web Design and Development project
            </small>
        </div>
    </footer>

    <script>
    // Flash messages: click × to dismiss, auto-dismiss after 6 seconds.
    (function () {
        document.querySelectorAll('.flash').forEach(function (el) {
            var dismiss = function () {
                el.classList.add('flash--leaving');
                setTimeout(function () { el.remove(); }, 220);
            };
            var btn = el.querySelector('.flash__close');
            if (btn) btn.addEventListener('click', dismiss);
            setTimeout(dismiss, 6000);
        });
    })();
    // Ctrl/Cmd+Enter inside any textarea submits its form.
    (function () {
        document.querySelectorAll('textarea').forEach(function (ta) {
            ta.addEventListener('keydown', function (e) {
                if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
                    var form = ta.closest('form');
                    if (form) form.submit();
                }
            });
        });
    })();
    </script>
</body>
</html>
