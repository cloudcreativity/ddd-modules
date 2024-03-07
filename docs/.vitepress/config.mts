import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "DDD Modules",
    description: "Modules for domain-driven implementations in PHP.",
    themeConfig: {
        // https://vitepress.dev/reference/default-theme-config
        nav: [
            { text: 'Guide', link: '/guide/' },
        ],

        sidebar: {
           '/guide/': [
               {
                   text: 'Concepts',
                   collapsed: false,
                   items: [
                       { text: 'Layers', link: '/guide/concepts/layers' },
                       { text: 'Encapsulation', link: '/guide/concepts/encapsulation' },
                   ],
               },
           ],
        },

        socialLinks: [
            { icon: 'github', link: 'https://github.com/cloudcreativity/ddd-modules' },
        ],

        footer: {
            message: 'Released under the MIT License.',
            copyright: 'Copyright © 2024 Cloud Creativity Ltd'
        }
    }
})
