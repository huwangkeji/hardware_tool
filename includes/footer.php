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
</body>
</html>
