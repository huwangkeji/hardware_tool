        </div>
    </main>
    <footer class="site-footer">
        <div class="container">
            <div class="footer-content">
                <p><?php echo !empty($settings['footer_copyright']) ? h($settings['footer_copyright']) : '&copy; ' . date('Y') . ' ' . h($settings['site_title']) . '. All rights reserved.'; ?></p>
                <p><?php echo !empty($settings['footer_version']) ? 'Version ' . h($settings['footer_version']) : 'Version ' . VERSION; ?></p>
            </div>
        </div>
    </footer>
    <script src="/assets/js/main.js"></script>
    <script src="/assets/js/modal.js"></script>
    <?php if (isset($extra_js)): ?>
        <?php foreach ($extra_js as $js): ?>
            <script src="<?php echo h($js); ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    <script>
    // 手机端检测与提示
    (function() {
        function isMobile() {
            return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)
                || (window.innerWidth <= 768 && 'ontouchstart' in window);
        }
        if (isMobile()) {
            document.addEventListener('DOMContentLoaded', function() {
                Modal.alert(
                    '本工具基于 Web Serial API 进行串口通信，仅支持电脑端浏览器（Chrome 89+）使用。<br><br>手机端仅可浏览页面内容作为参考，无法进行串口连接和调试操作。',
                    'warning',
                    '手机端访问提示'
                );
            });
        }
    })();
    </script>
</body>
</html>
