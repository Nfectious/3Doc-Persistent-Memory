# Contributing to Valkyrie Memory System

First off, thank you for considering contributing to Valkyrie Memory System! It's people like you that make this tool better for everyone.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [Development Setup](#development-setup)
- [How to Contribute](#how-to-contribute)
- [Coding Standards](#coding-standards)
- [Commit Guidelines](#commit-guidelines)
- [Pull Request Process](#pull-request-process)
- [Reporting Bugs](#reporting-bugs)
- [Suggesting Features](#suggesting-features)

---

## Code of Conduct

This project adheres to a Code of Conduct that we expect all contributors to follow. Please read [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md) before contributing.

**Key principles:**
- Be respectful and inclusive
- Focus on technical merit
- Constructive feedback only
- No harassment or discrimination
- Assume good intentions

---

## Getting Started

### Prerequisites

- Git
- Linux environment (Ubuntu 20.04+ recommended) or WSL on Windows
- PHP 7.4+
- Nginx or Apache
- Text editor or IDE
- Basic knowledge of:
  - PHP
  - JavaScript
  - Bash scripting
  - REST APIs
  - Markdown

### Fork and Clone

```bash
# 1. Fork the repository on GitHub

# 2. Clone your fork
git clone https://github.com/YOUR_USERNAME/valkyrie-memory-system.git
cd valkyrie-memory-system

# 3. Add upstream remote
git remote add upstream https://github.com/ORIGINAL_OWNER/valkyrie-memory-system.git

# 4. Create a branch
git checkout -b feature/your-feature-name
```

---

## Development Setup

### Local Installation

```bash
# 1. Install dependencies
sudo apt update
sudo apt install nginx php7.4-fpm php7.4-cli php7.4-json

# 2. Configure test environment
sudo bash scripts/dev-setup.sh

# 3. Start development server
sudo systemctl start nginx php7.4-fpm

# 4. Access test instance
# http://localhost/valkyrie
```

### Directory Structure

```
valkyrie-memory-system/
├── src/              # Source code
│   ├── web/          # Web interface
│   ├── cli/          # Command-line tools
│   └── config/       # Configuration
├── scripts/          # Installation & utility scripts
├── docs/             # Documentation
├── tests/            # Test files
└── examples/         # Example files
```

### Testing Your Changes

```bash
# Run API tests
bash tests/api-tests.sh

# Test web interface
# Open http://localhost/valkyrie in browser

# Test CLI tools
cd src/cli
./vproject list
./vproject create test_project
```

---

## How to Contribute

### Types of Contributions

We welcome all types of contributions:

#### 🐛 Bug Fixes
- Fix broken functionality
- Resolve errors
- Improve stability

#### ✨ New Features
- Add new functionality
- Enhance existing features
- Improve user experience

#### 📚 Documentation
- Fix typos
- Improve clarity
- Add examples
- Write tutorials

#### 🎨 UI/UX Improvements
- Better design
- Improved accessibility
- Mobile responsiveness

#### ⚡ Performance
- Optimize code
- Reduce resource usage
- Faster loading

#### 🧪 Tests
- Add test coverage
- Improve test quality
- Fix test failures

---

## Coding Standards

### PHP Code Style

```php
<?php
/**
 * Function description
 *
 * @param string $param Description
 * @return array Result
 */
function functionName($param) {
    // Use camelCase for variables
    $myVariable = "value";
    
    // Use clear, descriptive names
    $projectDirectory = "/path/to/project";
    
    // Add comments for complex logic
    // Calculate the total memory size
    $totalSize = calculateSize($projectDirectory);
    
    return [
        'success' => true,
        'data' => $totalSize
    ];
}
```

**PHP Standards:**
- Follow PSR-12 coding style
- Use type hints where possible
- Add PHPDoc comments
- Handle errors gracefully
- Validate all inputs
- Sanitize outputs

### JavaScript Code Style

```javascript
/**
 * Function description
 * @param {string} projectId - The project identifier
 * @returns {Promise<Object>} The project data
 */
async function loadProject(projectId) {
    // Use camelCase for variables
    const projectData = await fetchProjectData(projectId);
    
    // Use const/let, never var
    const isValid = validateProject(projectData);
    
    // Return early for error conditions
    if (!isValid) {
        return { error: 'Invalid project' };
    }
    
    return projectData;
}
```

**JavaScript Standards:**
- Use ES6+ features
- Async/await for promises
- Clear error handling
- JSDoc comments
- Descriptive variable names

### Bash Script Style

```bash
#!/bin/bash
# Script description
# Usage: script.sh [options]

set -e  # Exit on error

# Use UPPERCASE for constants
MEMORY_BASE="/opt/valkyrie/memory"
CONFIG_FILE="/etc/valkyrie/config"

# Use lowercase for variables
project_name="my_project"

# Check prerequisites
if [ ! -d "$MEMORY_BASE" ]; then
    echo "Error: Memory directory not found"
    exit 1
fi

# Functions for reusable code
create_project() {
    local name="$1"
    echo "Creating project: $name"
    mkdir -p "$MEMORY_BASE/projects/$name"
}
```

**Bash Standards:**
- Start with `#!/bin/bash`
- Use `set -e` for safety
- Check prerequisites
- Add usage documentation
- Handle errors properly

### Markdown Documentation

```markdown
# Clear Hierarchy

## Second Level Heading

### Third Level Heading

**Use bold for emphasis**

*Use italic for terms*

`Use backticks for code`

## Code Examples

\`\`\`bash
# Always specify language
command --flag value
\`\`\`

## Lists

- Clear bullet points
- One idea per bullet
- Parallel structure

## Links

[Link text](URL)
```

**Markdown Standards:**
- Use ATX-style headers (#)
- Add blank lines for readability
- Specify code block languages
- Use relative links to docs
- Keep lines under 120 characters

---

## Commit Guidelines

### Commit Message Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `style`: Code style (formatting, no functional change)
- `refactor`: Code refactoring
- `perf`: Performance improvement
- `test`: Adding or updating tests
- `chore`: Build process, dependencies, etc.

### Examples

```bash
# Good commits
git commit -m "feat(api): add multi-project memory endpoint"
git commit -m "fix(web): resolve copy button JavaScript error"
git commit -m "docs(readme): add installation instructions"

# Bad commits
git commit -m "fix stuff"
git commit -m "updates"
git commit -m "WIP"
```

### Commit Best Practices

- One logical change per commit
- Clear, descriptive messages
- Reference issues when applicable
- Keep commits focused and small
- Test before committing

---

## Pull Request Process

### Before Submitting

✅ **Checklist:**
- [ ] Code follows style guidelines
- [ ] Tests pass
- [ ] Documentation updated
- [ ] Commit messages follow guidelines
- [ ] Branch is up to date with main
- [ ] Self-review completed
- [ ] No merge conflicts

### Submission Process

1. **Update your branch**
   ```bash
   git fetch upstream
   git rebase upstream/main
   ```

2. **Push to your fork**
   ```bash
   git push origin feature/your-feature
   ```

3. **Create Pull Request**
   - Clear title and description
   - Reference related issues
   - Explain the changes
   - Add screenshots if UI changes
   - Request review from maintainers

4. **Respond to feedback**
   - Address reviewer comments
   - Make requested changes
   - Push updates to same branch

5. **Merge**
   - Maintainer will merge when approved
   - Delete your branch after merge

### Pull Request Template

```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Documentation
- [ ] Refactoring

## Testing
How was this tested?

## Screenshots (if applicable)
Add screenshots here

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] Tests added/updated
- [ ] All tests pass
```

---

## Reporting Bugs

### Before Reporting

- Search existing issues
- Test on latest version
- Reproduce the bug
- Gather system information

### Bug Report Template

```markdown
## Bug Description
Clear description of the bug

## Steps to Reproduce
1. Go to '...'
2. Click on '...'
3. See error

## Expected Behavior
What should happen

## Actual Behavior
What actually happens

## Screenshots
If applicable

## Environment
- OS: [e.g., Ubuntu 22.04]
- PHP Version: [e.g., 7.4]
- Browser: [e.g., Chrome 120]
- Version: [e.g., 4.1.0]

## Additional Context
Any other relevant information
```

---

## Suggesting Features

### Feature Request Template

```markdown
## Feature Description
Clear description of the feature

## Problem It Solves
What problem does this address?

## Proposed Solution
How should this work?

## Alternatives Considered
Other approaches considered

## Additional Context
Mockups, examples, references
```

### Good Feature Requests

- Solve a clear problem
- Align with project goals
- Include examples/mockups
- Consider existing functionality
- Discuss with community first

---

## Development Workflow

### Standard Workflow

```bash
# 1. Create feature branch
git checkout -b feature/amazing-feature

# 2. Make changes
# Edit files...

# 3. Test changes
bash tests/api-tests.sh

# 4. Commit
git add .
git commit -m "feat(api): add amazing feature"

# 5. Update from upstream
git fetch upstream
git rebase upstream/main

# 6. Push
git push origin feature/amazing-feature

# 7. Create PR
# Via GitHub interface
```

### Branch Naming

- `feature/description` - New features
- `fix/description` - Bug fixes
- `docs/description` - Documentation
- `refactor/description` - Refactoring
- `test/description` - Tests

---

## Questions?

- 💬 [GitHub Discussions](https://github.com/yourusername/valkyrie-memory-system/discussions)
- 🐛 [Issue Tracker](https://github.com/yourusername/valkyrie-memory-system/issues)
- 📧 Email: [your-email@example.com]

---

## Recognition

Contributors will be recognized in:
- [CONTRIBUTORS.md](CONTRIBUTORS.md)
- Release notes
- Project documentation

Thank you for contributing to Valkyrie Memory System! 🩸
