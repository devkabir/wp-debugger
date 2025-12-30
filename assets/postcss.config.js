const tailwindConfig = process.env.TW_CONFIG || './tailwind.page.config.js';

module.exports = {
    plugins: {
        'postcss-import': {},
        tailwindcss: {
            config: tailwindConfig,
        },
        autoprefixer: {},
        'postcss-prefix-selector': {
            prefix: '#wp-debugger',
        },
    }
};
