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
                        {text: 'Upgrading', link: '/guide/upgrade'},
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
                        {text: 'Events', link: '/guide/domain/events'},
                        {text: 'Services', link: '/guide/domain/services'},
                    ],
                },
                {
                    text: 'Application Layer',
                    collapsed: false,
                    items: [
                        {text: 'Commands', link: '/guide/application/commands'},
                        {text: 'Queries', link: '/guide/application/queries'},
                        {text: 'Integration Events', link: '/guide/application/integration-events'},
                        {text: 'Domain Events', link: '/guide/application/domain-events'},
                        {text: 'Units of Work', link: '/guide/application/units-of-work'},
                        {text: 'Asynchronous Processing', link: '/guide/application/asynchronous-processing'},
                    ],
                },
                {
                    text: 'Infrastructure Layer',
                    collapsed: false,
                    items: [
                        {text: 'Dependency Injection', link: '/guide/infrastructure/dependency-injection'},
                        {text: 'Exception Reporting', link: '/guide/infrastructure/exception-reporting'},
                        {text: 'Persistence', link: '/guide/infrastructure/persistence'},
                        {text: 'Publishing Events', link: '/guide/infrastructure/publishing-events'},
                        {text: 'Transactional Outbox', link: '/guide/infrastructure/outbox'},
                        {text: 'Queues', link: '/guide/infrastructure/queues'},
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
