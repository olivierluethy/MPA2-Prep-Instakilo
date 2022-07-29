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