export default {
    testEnvironment: 'jsdom',

    // Define root directory for tests - scan both core and plugins
    roots: [
        '<rootDir>',
        '<rootDir>/../../Plugins',
        '<rootDir>/../../PrivatePlugins'
    ],

    // Test file patterns
    testMatch: [
        '**/__tests__/**/*.js',
        '**/*.test.js'
    ],

    // Module name mapper for resolving imports between core and plugins
    moduleNameMapper: {
        '^@/(.*)$': '<rootDir>/$1',
        '^@plugins/(.*)$': '<rootDir>/../../Plugins/$1',
        '^@private-plugins/(.*)$': '<rootDir>/../../PrivatePlugins/$1'
    },

    // Collect coverage from both core and plugins
    collectCoverageFrom: [
        '**/*.js',
        '!**/*.test.js',
        '!**/node_modules/**',
        '!**/vendor/**',
        '!**/dist/**',
        '!**/build/**'
    ],

    // Ignore patterns
    testPathIgnorePatterns: [
        '/node_modules/',
        '/vendor/',
        '/dist/',
        '/build/'
    ],


    // Set transform to empty object to use native Node.js ES module support
    transform: {}
};
