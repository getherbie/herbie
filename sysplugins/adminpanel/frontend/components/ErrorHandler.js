import m from "mithril";

const ErrorHandler = {
    show(error) {
        // not authenticated
        if (error.code == 401) {
            m.route.set("/login");
        }
        alert(error.response.message);
    }
}

export default ErrorHandler;
