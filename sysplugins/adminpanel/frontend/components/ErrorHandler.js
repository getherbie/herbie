import m from "mithril";

const ErrorHandler = {
    show(error) {
        // not authenticated
        if (error.code == 401) {
            m.route.set("/login");
        }
        /*
        let el = document.getElementById('flashError');
        el.innerText = error.response.message;
        el.classList.remove('hidden');
        */
        alert(error.response.message);
    }
}

export default ErrorHandler;
