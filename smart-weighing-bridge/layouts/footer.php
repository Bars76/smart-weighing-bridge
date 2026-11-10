<?php
/**
 * Layout Footer - Smart Weighing Bridge
 * Menutup main-body, main, dashboard-layout, footer, body, html
 */

$navPrefix = isset($navPrefix) ? $navPrefix : '';
?>
            </div> <!-- .main-body -->
        </main>

        <footer class="app-footer" style="padding: 20px 32px; border-top: 1px solid #e2e8f0; background: #ffffff; color: #64748b; font-size: 12px; margin-top: auto; flex-shrink: 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
                <div>Smart Weighing Bridge &copy; 2026 - Sistem Monitoring Berat Kendaraan Berbasis ESP32 &amp; RFID</div>
                <div>Login sebagai: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? '-'); ?></strong> | <a href="<?php echo $navPrefix; ?>logout.php" style="color:#3b82f6;">Keluar</a></div>
            </div>
        </footer>
    </div> <!-- .dashboard-layout -->
</body>
</html>