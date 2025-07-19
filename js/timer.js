    // Set the date we're counting down to (Sri Lanka time)
    var countDownDate = new Date("Aug 26, 2025 00:00:00").getTime();

    // Update the count down every 1 second
    var x = setInterval(function() {
        // Get current date and time
        var now = new Date().getTime();

        // Find the distance between now and the count down date
        var distance = countDownDate - now;

        // Time calculations
        var days = Math.floor(distance / (1000 * 60 * 60 * 24));
        var hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        var seconds = Math.floor((distance % (1000 * 60)) / 1000);

        // Display the results
        document.getElementById("days").innerHTML = days;
        document.getElementById("hours").innerHTML = hours;
        document.getElementById("minutes").innerHTML = minutes;
        document.getElementById("seconds").innerHTML = seconds;

        // If the countdown is finished, display message
        if (distance < 0) {
            clearInterval(x);
            document.getElementById("rm").innerHTML = "";
            document.getElementsByClassName("register")[0].style.display = "none";
            document.getElementsByClassName("brand-logos")[0].style.display = "none";
                        document.getElementById("countdown").innerHTML = "<span style='color:rgb(0, 255, 0); font-size: 48px; font-weight: bold; text-transform: uppercase; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); font-family: \"Smooch Sans\", serif; margin: 50px 0;'>Registrations Are Closed</span>";
                    }
        }, 1000);