/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./templates/**/*.html"],
    theme: {
        extend: {
            zIndex: {
                'wp': '999999',
            }
        },
    },
    plugins: [],
}
