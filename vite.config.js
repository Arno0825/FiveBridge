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
    //     // host: '172.22.48.1',
    //     proxy: {
    //         '/ws': {
    //             target: 'ws://127.0.0.1:9502',
    //             changeOrigin: true,
    //             ws: true,
    //             rewrite: (p) => p.replace(/^\/ws/, '')
    //         }
    //     }
    // },
    devServer: {
        proxy: {
            '/ws': {
                target: 'ws://localhost:9502', // WebSocket服务器的地址
                ws: true,
                changeOrigin: true,
            },
        },
    },
    resolve: {
        alias: {
            vue: 'vue/dist/vue.esm-bundler.js',
        },

    },
    // build: {
    //     outDir: 'public',
    // },
});
