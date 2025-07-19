const messages = [  
    ' . . .',
    ' 🔥 Initializing Network Connection ...',  
    ' 🎮 Retrieving Necessary Assets ...',  
    ' 🏆 Requesting Server Resources...',  
    ' 🔒 Establishing Secure Session...',  
    ' 🚀 Connection Successful...',
    ' ☠️ Ready for Operations... ' 
];  

window.addEventListener('load', function() {
    const terminalContent = document.getElementById('terminal-content');
    const skipButton = document.getElementById('skip-button');
    let messageIndex = 0;
    let currentInterval;

    // Hide main content and pause all animations
    document.body.style.visibility = 'hidden';
    document.documentElement.style.setProperty('--animation-play-state', 'paused');
    document.getElementById('loading-screen').style.visibility = 'visible';

    function skipAnimation() {
        if (currentInterval) clearInterval(currentInterval);
        document.getElementById('loading-screen').style.opacity = '0';
        document.body.style.visibility = 'visible';
        document.documentElement.style.setProperty('--animation-play-state', 'running');
        setTimeout(() => {
            document.getElementById('loading-screen').style.display = 'none';
        }, 500);
    }

    skipButton.addEventListener('click', skipAnimation);

    function typeMessage(message) {
        let i = 0;
        currentInterval = setInterval(() => {
            terminalContent.innerHTML += message.charAt(i);
            i++;
            if (i === message.length) {
                clearInterval(currentInterval);
                terminalContent.innerHTML += '<br>>';
                messageIndex++;
                if (messageIndex < messages.length) {
                    setTimeout(() => typeMessage(messages[messageIndex]), 500);
                } else {
                    setTimeout(skipAnimation, 1000);
                }
            }
        }, 15);
    }

    typeMessage(messages[0]);
});