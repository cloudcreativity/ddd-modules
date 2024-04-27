import {defineConfig} from 'vitepress'

// https://vitepress.dev/reference/site-config
export default defineConfig({
    title: "DDD Modules",
    description: "Modules for domain-driven implementations in PHP.",
    base: '/ddd-modules/',
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
                        {text: 'Modularisation & Structure', link: '/guide/concepts/modularisation'},
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
                    text: 'Application Layer',
                    collapsed: false,
                    items: [
                        {text: 'Commands', link: '/guide/application/commands'},
                        {text: 'Queries', link: '/guide/application/queries'},
                        {text: 'Integration Events', link: '/guide/application/events'},
                        {text: 'Domain Event Dispatching', link: '/guide/application/domain-event-dispatchers'},
                        {text: 'Asynchronous Processing', link: '/guide/application/asynchronous-processing'},
                    ],
                },
                {
                    text: 'Infrastructure Layer',
                    collapsed: false,
                    items: [
                        {text: 'Asynchronous Processing', link: '/guide/infrastructure/queues'},
                        {text: 'Exception Reporting', link: '/guide/infrastructure/exception-reporting'},
                        {text: 'Outbox & Inbox', link: '/guide/infrastructure/outbox-inbox'},
                        {text: 'Persistence', link: '/guide/infrastructure/persistence'},
                        {text: 'Units of Work', link: '/guide/infrastructure/units-of-work'},
                    ],
                },
                {
                    text: 'Toolkit',
                    collapsed: true,
                    items: [
                        {text: 'Assertions', link: '/guide/toolkit/assertions'},
                        {text: 'Identifiers', link: '/guide/toolkit/identifiers'},
                        {text: 'Iterables', link: '/guide/toolkit/iterables'},
                        {text: 'Results', link: '/guide/toolkit/results'},
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
