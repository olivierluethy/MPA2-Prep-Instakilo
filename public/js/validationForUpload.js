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
        if (document.querySelector('#myFile') != null) {
            if (document.querySelector('#myFile').value.trim() === '') {
                document.querySelector('#myFile').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte wählen Sie ein Bild aus!</label>");
                errors = true;
            }
        }
        if (document.querySelector('#title') != null) {
            if (document.querySelector('#title').value.trim() === '') {
                document.querySelector('#title').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte geben Sie Ihrem Post einen Titel!</label>");
                errors = true;
            }
        }
        if (document.querySelector('#beschreibung') != null) {
            if (document.querySelector('#beschreibung').value.trim() === '') {
                document.querySelector('#beschreibung').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte geben Sie Ihrem Post eine Beschreibung!</label>");
                errors = true;
            }
        }
        if (document.querySelector('#datum') != null) {
            if (document.querySelector('#datum').value.trim() === '') {
                document.querySelector('#datum').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte geben Sie ein Datum an!</label>");
                errors = true;
            }
        }
        if (document.querySelector('#ort') != null) {
            if (document.querySelector('#ort').value.trim() === '') {
                document.querySelector('#ort').insertAdjacentHTML("afterend", "<label class=\"warning\"> Bitte geben Sie ein Ort an!</label>");
                errors = true;
            }
        }
        if (errors) {
            evt.preventDefault();
        }
    });
});