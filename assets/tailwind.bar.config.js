/** @type {import('tailwindcss').Config} */
module.exports = {
    content: ["./templates/**/*.html"],
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
