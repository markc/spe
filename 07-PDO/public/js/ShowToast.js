(() => {
    window.showToast = (m, t) => {
        const tc = document.createElement("div");
        tc.setAttribute("aria-live", "polite");
        tc.setAttribute("aria-atomic", "true");
        Object.assign(tc.style, { position: "fixed", top: "20px", right: "20px", zIndex: "1050" });
        tc.innerHTML = `<div class="toast align-items-center text-white bg-${t} border-0" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">${m}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`;
        document.body.appendChild(tc);
        const te = tc.querySelector(".toast");
        const toast = new bootstrap.Toast(te, { autohide: !0, delay: 3e3 });
        toast.show();
        te.addEventListener("hidden.bs.toast", () => tc.remove());
    };
})();

/*
function showToast(message, type) {
    const toastContainer = document.createElement("div");
    toastContainer.setAttribute("aria-live", "polite");
    toastContainer.setAttribute("aria-atomic", "true");
    toastContainer.style.position = "fixed";
    toastContainer.style.top = "20px";
    toastContainer.style.right = "20px";
    toastContainer.style.zIndex = "1050";

    toastContainer.innerHTML = 
        '<div class="toast align-items-center text-white bg-' + type + ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
            '<div class="d-flex">' +
                '<div class="toast-body">' + message + '</div>' +
                '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>' +
            '</div>' +
        '</div>';

    document.body.appendChild(toastContainer);
    const toastElement = toastContainer.querySelector(".toast");
    const toast = new bootstrap.Toast(toastElement, {
        autohide: true,
        delay: 3000
    });
    toast.show();

    toastElement.addEventListener("hidden.bs.toast", () => {
        toastContainer.remove();
    });
}
*/
