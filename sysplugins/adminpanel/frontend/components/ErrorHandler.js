const ErrorHandler = {
    show(error) {
        alert(error.response.message);
    }
}

export default ErrorHandler;
