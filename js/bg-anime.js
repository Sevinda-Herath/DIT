const canvas = document.createElement('canvas');
canvas.style.position = 'fixed';
canvas.style.top = '0';
canvas.style.left = '0';
canvas.style.width = '100%';
canvas.style.height = '100%';
canvas.style.pointerEvents = 'none';
canvas.style.zIndex = '-999';
document.body.appendChild(canvas);

const ctx = canvas.getContext('2d');
const letters = 'ABCDEFGHIJKLMNSEVINDAXYZABCDEFGHIJKLMNOPHERATHXYZABCDEFGHIFUCKNOPQRSTUVXYZYOUDEFGHIJKLMNOPQRSTUVXYZ';
const numbers = '0123456789';
const alphabet = letters + numbers;

let fontSize = 10;
let columns = canvas.width / fontSize;
let drops = [];
let animationInterval;

function initDrops() {
    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;
    columns = canvas.width / fontSize;
    drops = [];
    for (let i = 0; i < columns; i++) {
        drops[i] = 1;
    }
}

function draw() {
    ctx.fillStyle = 'rgba(0, 0, 0, 0.05)';
    ctx.fillRect(0, 0, canvas.width, canvas.height);
    ctx.fillStyle = '#0F0';
    ctx.font = fontSize + 'px monospace';

    for (let i = 0; i < drops.length; i++) {
        const text = alphabet[Math.floor(Math.random() * alphabet.length)];
        ctx.fillText(text, i * fontSize, drops[i] * fontSize);
        if (drops[i] * fontSize > canvas.height && Math.random() > 0.975) {
            drops[i] = 0;
        }
        drops[i]++;
    }
}

window.addEventListener('resize', initDrops);

// Initialize and start animation with delay
initDrops();
setTimeout(() => {
    animationInterval = setInterval(draw, 33);
}, 9000); // 2000ms = 2 seconds delay