// Background Title Animation Effects
document.addEventListener('DOMContentLoaded', function() {
    const title = document.querySelector('.title');
    if (!title) return;

    // Mouse move parallax effect
    document.addEventListener('mousemove', function(e) {
        const x = (e.clientX / window.innerWidth) - 0.5;
        const y = (e.clientY / window.innerHeight) - 0.5;
        
        const moveX = x * 20;
        const moveY = y * 20;
        
        title.style.transform = `translate(calc(-50% + ${moveX}px), calc(-50% + ${moveY}px))`;
    });

    // Scroll-based opacity change
    window.addEventListener('scroll', function() {
        const scrollPercent = window.scrollY / (document.body.scrollHeight - window.innerHeight);
        const opacity = Math.max(0.02, 0.15 - (scrollPercent * 0.1));
        title.style.opacity = opacity;
    });

    // Typing effect animation
    const originalText = title.textContent;
    let currentText = '';
    let isDeleting = false;
    let charIndex = 0;
    
    function typeWriter() {
        if (!isDeleting && charIndex < originalText.length) {
            currentText += originalText.charAt(charIndex);
            charIndex++;
        } else if (isDeleting && charIndex > 0) {
            currentText = currentText.slice(0, -1);
            charIndex--;
        }
        
        title.textContent = currentText;
        
        let typeSpeed = isDeleting ? 50 : 100;
        
        if (charIndex === originalText.length && !isDeleting) {
            setTimeout(() => {
                isDeleting = true;
                typeWriter();
            }, 3000);
            return;
        } else if (charIndex === 0 && isDeleting) {
            isDeleting = false;
            setTimeout(typeWriter, 1000);
            return;
        }
        
        setTimeout(typeWriter, typeSpeed);
    }

    // Start typing effect after a delay
    setTimeout(() => {
        title.textContent = '';
        typeWriter();
    }, 2000);

    // Glitch effect on click
    title.addEventListener('click', function() {
        title.classList.add('glitch-effect');
        setTimeout(() => {
            title.classList.remove('glitch-effect');
        }, 500);
    });

    // Random color pulse effect
    setInterval(() => {
        const randomOpacity = Math.random() * 0.1 + 0.05;
        const randomHue = Math.random() * 60 + 90; // Green to cyan range
        
        title.style.color = `hsla(${randomHue}, 100%, 50%, ${randomOpacity})`;
        
        setTimeout(() => {
            title.style.color = 'rgba(0, 255, 0, 0.1)';
        }, 200);
    }, 5000);

    // Window resize handler
    window.addEventListener('resize', function() {
        // Reset transform on resize to prevent positioning issues
        setTimeout(() => {
            title.style.transform = 'translate(-50%, -50%)';
        }, 100);
    });

    // Touch device handling
    if ('ontouchstart' in window) {
        document.addEventListener('touchmove', function(e) {
            const touch = e.touches[0];
            const x = (touch.clientX / window.innerWidth) - 0.5;
            const y = (touch.clientY / window.innerHeight) - 0.5;
            
            const moveX = x * 10; // Reduced movement for touch
            const moveY = y * 10;
            
            title.style.transform = `translate(calc(-50% + ${moveX}px), calc(-50% + ${moveY}px))`;
        });
    }
});
