export default class extends Stimulus.Controller {
    static get targets() {
        return ["name"]
    }

    addWithKeyboard(event) {
        if (event.keyCode == 13) {
            this.add()
        }
    }

    add(event) {
        let params = {
            name: this.name
        };
        fetch('test/add', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                "Content-Type": "application/json",
                // "Content-Type": "application/x-www-form-urlencoded",
            },
            body: JSON.stringify(params)
        }).then((response) => {
            return response.json();
        }).then((data) => {

            // Tabelle mit dem existierenden HTML tbody und der Zeile (row) aus dem template Element instantiieren
            let row = document.getElementById('test-row');
            let tds = row.content.querySelectorAll("td");
            tds[0].textContent = data.name;

            // Neue Zeile (row) klonen und in die Tabelle einfügen
            var table = document.getElementById("test-tbody");
            var clone = document.importNode(row.content, true);
            table.appendChild(clone);

            this.name = '';
        });
    }

    delete(event) {
        if (confirm('Datensatz löschen?')) {
            fetch('test/' + '29', {
                method: 'POST',
                body: {id: 'test'}
            }).then(function (response) {
                return JSON.stringify(response.json());
            }).then(function (success) {
                if (success) {
                    let closest = event.target.closest('tr');
                    closest.style.display = 'none';
                    console.log('okay');
                }
            });
        }
    }

    get name() {
        return this.nameTarget.value
    }

    set name(value) {
        this.nameTarget.value = value
    }
}
