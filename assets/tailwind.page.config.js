/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./templates/page/*.html"],
    theme: {
        extend: {
            zIndex: {
                'wp': '999999',
            }
        },
    },
    plugins: [],
}
