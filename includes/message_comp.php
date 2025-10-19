<?php
/**
 * Başarı ($success) ve Hata ($error) mesajlarını modern ve otomatik kaybolan
 * bir uyarı kutusu (alert box) içinde gösteren PHP bileşenidir.
 * Kullanımı:
 * 1. Çağırılacak sayfada $success veya $error değişkenlerini tanımlayın.
 * 2. require_once __DIR__ . '/message_compt.php'; ile dahil edin.
*/

$has_messages = (isset($success) && $success) || (isset($error) && $error);

if ($has_messages):
?>

<?php if (isset($success) && $success): ?>
    <div class="alert-box alert-success" role="alert">
        <div class="alert-icon">🎉</div>
        <div class="alert-content">
            <p><strong>Başarılı!</strong></p>
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    </div>
<?php endif; ?>

<?php if (isset($error) && $error): ?>
    <div class="alert-box alert-error" role="alert">
        <div class="alert-icon">🚨</div>
        <div class="alert-content">
            <p><strong>İşlem Başarısız!</strong></p>
            <ul>
                <?php
                // Hata mesajı bir dizi ise liste olarak göster
                if (is_array($error)):
                    foreach ($error as $err): ?>
                        <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach;
                // Hata mesajı string ise tek bir madde olarak göster
                else: ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-box');
    const displayDuration = 4000; // 4 saniye

    alerts.forEach(alertBox => {
        // Otomatik kaybolma
        setTimeout(() => {
            alertBox.style.opacity = '0';
            alertBox.addEventListener('transitionend', () => {
                alertBox.remove(); 
            });

        }, displayDuration);
        
        // Kullanıcı Tıklamasıyla Kapatma
        alertBox.style.cursor = 'pointer';
        alertBox.addEventListener('click', function() {
            alertBox.style.opacity = '0';
            alertBox.addEventListener('transitionend', () => {
                alertBox.remove();
            });
        });
    });
});
</script>

<?php endif;?>