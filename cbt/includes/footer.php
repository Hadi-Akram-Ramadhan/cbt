<?php if (isLoggedIn()): ?>
        </div><!-- /.p-6 -->
    </main>
</div><!-- /.flex -->
<?php else: ?>
</div><!-- /.min-h-screen -->
<?php endif; ?>

<script>
// Live clock
function updateClock() {
    const el = document.getElementById('currentTime');
    if (el) {
        const now = new Date();
        el.textContent = now.toLocaleTimeString('id-ID', {hour:'2-digit',minute:'2-digit'});
    }
}
setInterval(updateClock, 1000);

// Auto-dismiss flash messages
setTimeout(() => {
    const msg = document.getElementById('flashMsg');
    if (msg) msg.remove();
}, 5000);
</script>
</body>
</html>
