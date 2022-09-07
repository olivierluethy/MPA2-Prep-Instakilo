<!-- The Modal -->
<div id="myModal" class="modal">
    <!-- Modal content -->
    <div class="modal-content">
        <div class="modal-header">
            <span class="close">&times;</span>
            <h2>Upload Image</h2>
        </div>
        <div class="modal-body">
            <form action="imageUpload" method="POST" enctype="multipart/form-data"><br>
                <label for="file">Choose File:</label><br>
                <input type="file" id="myFile" name="filename"><br>
                <label for="title">Titel:</label><br>
                <input type="text" id="title" name="title" placeholder="Bitte Titel eingeben"><br><br>
                <label for="beschreibung">Beschreibung:</label><br>
                <textarea id="story" name="beschreibung" rows="5" cols="71"
                    placeholder="Schreiben sie eine Beschreibung"></textarea><br><br>
                <label for="datum">Datum:</label><br>
                <input type="date" id="datum" name="datum"><br><br>
                <label for="ort">Ort:</label><br>
                <input type="text" id="ort" name="ort" placeholder="Bitte Ort eingeben"><br><br>
                <label for="oeffentlich">Öffentlich:</label><br>
                <input type="checkbox" id="oeffentlich" name="oeffentlich" value="Yes"><br><br>
                <input type="submit" value="Hochladen">
            </form>
        </div>
    </div>
</div>