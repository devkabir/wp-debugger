module.exports = {
    plugins: {
        tailwindcss: {
            config: './tailwind.page.config.js',
        },
        autoprefixer: {},
        'postcss-prefix-selector': {
            prefix: '.wp-debugger',
        },
    }
}