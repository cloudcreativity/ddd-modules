import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "DDD Modules",
    description: "Modules for domain-driven implementations in PHP.",
    themeConfig: {
        // https://vitepress.dev/reference/default-theme-config
        nav: [
            {
                text: 'Guide',
                link: '/guide/',
            },
        ],

        sidebar: {
            '/guide/': [
                {
                    text: 'Introduction',
                    collapsed: false,
                    items: [
                        {text: 'What is DDD Modules?', link: '/guide/'},
                        {text: 'Installation', link: '/guide/installation'},
                    ],
                },
                {
                    text: 'Core Concepts',
                    collapsed: false,
                    items: [
                        {text: 'Overview', link: '/guide/concepts/'},
                        {text: 'Bounded Contexts', link: '/guide/concepts/bounded-contexts'},
                        {text: 'Layers', link: '/guide/concepts/layers'},
                        {text: 'Encapsulation', link: '/guide/concepts/encapsulation'},
                        {text: 'Modularisation', link: '/guide/concepts/modularisation'},
                    ],
                },
                {
                    text: 'Domain Layer',
                    collapsed: false,
                    items: [
                        {text: 'Entities & Aggregates', link: '/guide/domain/entities'},
                        {text: 'Value Objects', link: '/guide/domain/value-objects'},
                        {text: 'Domain Events', link: '/guide/domain/events'},
                        {text: 'Services', link: '/guide/domain/services'},
                    ],
                },
                {
                    text: 'Infrastructure Layer',
                    collapsed: false,
                    items: [],
                },
                {
                    text: 'Application Layer',
                    collapsed: false,
                    items: [],
                },
                {
                    text: 'Toolkit',
                    collapsed: true,
                    items: [
                        {text: 'Assertions', link: '/guide/toolkit/assertions'},
                        {text: 'Identifiers', link: '/guide/toolkit/identifiers'},
                        {text: 'Iterables', link: '/guide/toolkit/iterables'},
                    ],
                }
            ],
        },

        socialLinks: [
            {
                icon: 'github',
                link: 'https://github.com/cloudcreativity/ddd-modules',
            },
        ],

        footer: {
            message: 'Released under the MIT License.',
            copyright: 'Copyright © 2024 Cloud Creativity Ltd',
        },

        outline: 'deep',
    },
})
