// Clientside Validierung
window.addEventListener("load", function() {

    document.querySelector('form').addEventListener('submit', function(evt) {

        var errors = false;
        var warnings = document.querySelectorAll(".warning");
        if (warnings != null) {
            warnings.forEach(element => {
                element.remove();
            });
        }
        if (document.querySelector('#name') != null) {
            if (document.querySelector('#name').value.trim() === '') {
                document.querySelector('#name').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte gib einen Namen ein</label>");
                errors = true;
            }
        }
        if (document.querySelector('#vorname') != null) {
            if (document.querySelector('#vorname').value.trim() === '') {
                document.querySelector('#vorname').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte gib einen Vorname ein</label>");
                errors = true;
            }
        }
        if (document.querySelector('#email') != null) {
            if (document.querySelector('#email').value.trim() === '' || !document.querySelector('#email').value.trim().includes("@")) {
                document.querySelector('#email').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte gib eine gültige Email ein.</label>");
                errors = true;
            }
        }
        if (errors) {
            evt.preventDefault();
        }
    });
});