var login = document.getElementById("login");
var register = document.getElementById("register");

function navSwitch(num){
    /* Register */
    if(num == 1){
        login.style.display="none";
        document.getElementById("registerButton").style.backgroundColor="white";
        document.getElementById("loginButton").style.backgroundColor="black";

        document.getElementById("registerButton").style.color="black";
        document.getElementById("loginButton").style.color="white";
        register.style.display="block";
    }else {
        login.style.display="block";
        register.style.display="none";

        document.getElementById("registerButton").style.backgroundColor="black";
        document.getElementById("loginButton").style.backgroundColor="white";

        document.getElementById("registerButton").style.color="white";
        document.getElementById("loginButton").style.color="black";
    }
}

// Clientside Validierung - Login
window.addEventListener("load", function() {
    this.document.getElementById("login") .addEventListener('submit', function(evt) {

        var errors = false;
        var warnings = document.querySelectorAll(".warning");
        if (warnings != null) {
            warnings.forEach(element => {
                element.remove();
            });
        }
        if (document.querySelector('#emailuser') != null) {
            if (document.querySelector('#emailuser').value.trim() === '') {
                document.querySelector('#emailuser').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte geben Sie einen Namen ein</label>");
                errors = true;
            }
        }
        if (document.querySelector('#password') != null) {
            if (document.querySelector('#password').value.trim() === '') {
                document.querySelector('#password').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte geben Sie das Passwort ein</label>");
                errors = true;
            } else if (document.querySelector('#password').value.length < 6) {
                document.querySelector('#password').insertAdjacentHTML("afterend", "<label class=\"warning\"> Passwort muss mindestens 6 Zeichen enthalten</label>");
                errors = true;
            }
        }
        if (errors) {
            evt.preventDefault();
        }
    });
});

// Clientside Validierung - Register
window.addEventListener("load", function() {
    this.document.getElementById("register") .addEventListener('submit', function(evt) {

        var errors = false;
        var warnings = document.querySelectorAll(".warning");
        if (warnings != null) {
            warnings.forEach(element => {
                element.remove();
            });
        }
        if (document.querySelector('#email') != null) {
            if (document.querySelector('#email').value.trim() === '') {
                document.querySelector('#email').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte geben Sie einen Namen ein</label>");
                errors = true;
            }
        }
        if (document.querySelector('#username') != null) {
            if (document.querySelector('#username').value.trim() === '') {
                document.querySelector('#username').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte geben Sie einen Nutzername ein</label>");
                errors = true;
            }
        }
        if (document.querySelector('#password') != null) {
            if (document.querySelector('#password').value.trim() === '') {
                document.querySelector('#password').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte geben Sie das Passwort ein</label>");
                errors = true;
            } else if (document.querySelector('#password').value.length < 6) {
                document.querySelector('#password').insertAdjacentHTML("afterend", "<label class=\"warning\"> Passwort muss mindestens 6 Zeichen enthalten</label>");
                errors = true;
            }
        }
        if (document.querySelector('#verypass') != null) {
            if (document.querySelector('#verypass').value.trim() === '') {
                document.querySelector('#verypass').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte Passwort erneut eingeben</label>");
                errors = true;
            } else if (document.querySelector('#password').value != document.querySelector('#passwort_again').value) {
                document.querySelector('#verypass').insertAdjacentHTML("afterend", "<label class=\"warning\"> Nicht das gleiche Passwort</label>");
                errors = true;
            }
        }
        if (errors) {
            evt.preventDefault();
        }
    });
});