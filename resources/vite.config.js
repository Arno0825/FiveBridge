import {defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';


export default defineConfig({
    plugins: [
        vue(),
        laravel([
            'resources/css/app.css',
            'resources/js/app.js',
        ]),
    ],
    // server: {
    //     host: '172.22.48.1',
    // },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },

    },
    // build: {
    //     outDir: 'public',
    // },
});
