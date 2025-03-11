var btnEntrar = document.querySelector("#entrar");
var btnRegistrar = document.querySelector("#registrar");
var body = document.querySelector("body");

btnEntrar.addEventListener("click", function () {
    body.className = "sign-in-js";
});

btnRegistrar.addEventListener("click", function () {
    body.className = "sign-up-js";
});