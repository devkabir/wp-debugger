/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./templates/bar/*.html"],
    theme: {
        extend: {
            'width': {
                fill: '-webkit-fill-available',
            }
        },
    },
    plugins: [],
    corePlugins: {
        preflight: false,        
    },
}
