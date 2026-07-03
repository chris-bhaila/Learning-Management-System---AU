import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'
import tailwindcss from '@tailwindcss/vite'

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
})


// For mobile
// import { defineConfig } from 'vite';
// import laravel from 'laravel-vite-plugin';

// export default defineConfig({
//     plugins: [
//         laravel({
//             input: ['resources/css/app.css', 'resources/js/app.js'],
//             refresh: true,
//         }),
//     ],
//     server: {
//         host: '0.0.0.0', // Exposes Vite to the local network
//         hmr: {
//             host: '192.168.1.13', // Replace with your exact computer IP address
//         },
//     },
// });

// import { defineConfig } from 'vite'

// export default defineConfig({
//   server: {
//     host: true, // or '0.0.0.0'
//     port: 5173, // optional, default is 5173
//   },
// })