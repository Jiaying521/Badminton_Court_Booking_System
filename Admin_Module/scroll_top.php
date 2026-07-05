<button id="scrollTopBtn" onclick="window.scrollTo({top:0, behavior:'smooth'})" aria-label="Back to top">
    <i class="fas fa-chevron-up"></i>
</button>

<style>
#scrollTopBtn {
    position: fixed;
    bottom: 32px;
    right: 32px;
    width: 46px;
    height: 46px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f59e0b, #d97706);
    color: #0f172a;
    border: none;
    font-size: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 16px rgba(245,158,11,0.4);
    opacity: 0;
    visibility: hidden;
    transform: translateY(12px);
    transition: opacity 0.3s, transform 0.3s, visibility 0.3s, box-shadow 0.3s;
    z-index: 999;
}
#scrollTopBtn.show {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}
#scrollTopBtn:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 24px rgba(245,158,11,0.55);
}
</style>

<script>
(function () {
    const btn = document.getElementById('scrollTopBtn');
    if (!btn) return;
    window.addEventListener('scroll', function () { // 监听website滚动事件
        btn.classList.toggle('show', window.scrollY > 300); // Scroll超过多少才会显示
    });
})();
</script>
