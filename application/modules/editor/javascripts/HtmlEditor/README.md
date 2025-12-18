# HtmlEditor - Build and Test Guide

This directory contains the HtmlEditor module and its testing infrastructure for the Translate5 application.

## 📋 Table of Contents

- [Prerequisites](#prerequisites)
- [Installation](#installation)
- [Building](#building)
- [Testing](#testing)
- [Development Workflow](#development-workflow)

---

## Prerequisites

### Required Software

- **Node.js** (v18 or higher)
- **npm** (comes with Node.js)

### Add the following section to the docker-compose.overrides.yml to create a Docker container for building and testing:
```dockerfile
    webpack:
        image: node:slim
        tty: true
        volumes:
            - application/modules/editor/:/home:rw,cached
            - public/modules/editor/js/HtmlEditor:/build:rw,cached
        entrypoint: "tail -f /dev/null"
        working_dir: /home
```

### All commands below assume you are either inside the `webpack` Docker container

---

## Installation

### 1. Install Dependencies

```bash
cd javascripts/HtmlEditor
npm install
```

This will install:
- webpack & webpack-cli
- Jest & jest-environment-jsdom
- CKEditor dependencies
- CSS loaders
- Other build tools

---

## Building

### Build All (HtmlEditor + All Plugins) 🚀

Build everything in one command:

```bash
./build-all.sh
```

This unified build script:
- ✅ Builds the main HtmlEditor
- ✅ Builds all plugin webpack configurations (TrackChanges, etc.)
- ✅ Automatically installs dependencies if needed
- ✅ Shows colored output with success/failure status
- ✅ Exits with error code if any build fails

**Add new plugins to build:** Edit `build-all.sh` and add more `build_project` calls.

### Development Build

Build the HtmlEditor bundle once:

```bash
npm run build
```

This runs webpack in development mode and creates the bundle with source maps.

### Production Build

For production deployment (minified, optimized):

```bash
npm run build:prod
```

### Watch Mode

Automatically rebuild on file changes:

```bash
npm run build:watch
```

### Build Output

Built files are located in public/modules/editor/js/HtmlEditor/.

---

## Testing

### Test Infrastructure

Tests use:
- **Jest** - Testing framework
- **jsdom** - Browser environment simulation
- **ES Modules** - Native Node.js module support

### Test Configuration

- **Config file**: `jest.config.mjs`
- **Test pattern**: `**/*.test.js`
- **Test locations**:
  - Core tests: `Tools/*.test.js`
  - Plugin tests: `../../Plugins/**/*.test.js`, `../../PrivatePlugins/**/*.test.js`

---

## Running Tests

### Login to the container

```bash
cd javascripts/HtmlEditor

# Run all tests
npm run test

# Run specific test file
npm run test -- split-node.test.js

# Run tests matching a pattern
npm run test -- -t "splitNodeByChild"

# Run in watch mode (auto-rerun on changes)
npm run test -- --watch

# Run specific test case
npm run test -- -t "split ins node with nested del in the middle"

# Run with verbose output
npm run test -- --verbose

# List all test files
npm run test -- --listTests

# Coverage report
npm test -- --coverage
```

---

## Plugin Tests

Tests can also be written for plugins located outside the core HtmlEditor:

```
application/modules/editor/
├── javascripts/HtmlEditor/            ← Core tests here
├── Plugins/                           ← Public plugins
│   └── SomePlugin/
│       └── **/*.test.js               ← Plugin tests
└── PrivatePlugins/                    ← Private plugins
    └── TrackChanges/
        └── public/js/custom/
            └── nested-track-changes-fixer.test.js  ← Plugin tests
```

All tests are automatically discovered by Jest.

---

## Development Workflow

### 1. Make Code Changes

Edit source files in:
- `Tools/` - Utility functions
- `Editor/` - Core editor modules
- Or plugin directories

### 2. Run Tests

```bash
# Watch mode for instant feedback
npm run test -- --watch
```

### 3. Build

```bash
npm run build
```

### 4. Verify

Check the built output and test in the application.

---

## Common Testing Patterns

### Test File Structure

```javascript
import { functionToTest } from './module.js';

describe('functionToTest', () => {
    test('should do something', () => {
        const result = functionToTest(input);
        expect(result).toBe(expected);
    });
    
    test('should handle edge case', () => {
        expect(() => functionToTest(null)).toThrow();
    });
});
```

### Testing with DOM

```javascript
test('should manipulate DOM correctly', () => {
    document.body.innerHTML = '<div id="test">Hello</div>';
    
    const element = document.querySelector('#test');
    expect(element.textContent).toBe('Hello');
});
```

### Data-Driven Tests

```javascript
describe.each([
    { input: 'a', expected: 'A' },
    { input: 'b', expected: 'B' },
])('uppercase conversion', ({ input, expected }) => {
    test(`should convert ${input} to ${expected}`, () => {
        expect(input.toUpperCase()).toBe(expected);
    });
});
```

---

## Troubleshooting

### Test Failures

```bash
# Run with verbose output to see details
./run-tests-docker.sh -- --verbose

# Run only failed tests
./run-tests-docker.sh -- --onlyFailures

# Clear Jest cache
./run-tests-docker.sh -- --clearCache
```

### Module Resolution Issues

Check `jest.config.mjs`:
- Verify `roots` includes all test directories
- Check `moduleNameMapper` for path aliases
- Ensure `testMatch` patterns are correct

---

## Test Coverage

### Generate Coverage Report

```bash
npm run test -- --coverage
```

This creates a coverage report in the `coverage/` directory.

### View Coverage

```bash
# Open coverage report in browser
open coverage/lcov-report/index.html
```

---

## Continuous Integration

For CI/CD pipelines, use:

```bash
# Run all tests non-interactively
npm test -- --ci --coverage --maxWorkers=2
```

---

## npm Scripts

Available in `package.json`:

```json
{
  "scripts": {
    "build": "webpack --mode development --config webpack.config.mjs",
    "test": "NODE_OPTIONS=--experimental-vm-modules jest"
  }
}
```

---

## License

This project is part of translate5. See the main project license for details.

---

**Last Updated**: December 11, 2025
