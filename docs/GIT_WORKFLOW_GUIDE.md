# Git Workflow Guide

## Overview

This guide explains how to use Git for version control in the KPI Monitoring System project.

## Initial Setup (Already Done)

```bash
# Initialize git repository
git init

# Add remote repository
git remote add origin https://github.com/ErnestEZY/kpi_monitoring_system.git

# Create .gitignore file
# (Already created)
```

## First Commit & Push

### Step 1: Check Status

```bash
# See what files are ready to commit
git status
```

### Step 2: Add Files

```bash
# Add all files
git add .

# Or add specific files/folders
git add api/
git add database/
git add docs/
git add supervisor/
git add README.md
```

### Step 3: Commit Changes

```bash
# Commit with descriptive message
git commit -m "Initial commit: Complete KPI Monitoring System v1.0"

# Or with detailed message
git commit -m "Initial commit: Complete KPI Monitoring System v1.0

- Added authentication system
- Implemented 21 KPIs across 7 performance groups
- Added 4 innovative features (Predictive Alerts, Training Recommendations, Anomaly Detection, Peer Comparison)
- Created database export/import system
- Added comprehensive documentation
- Included sample data and test scripts"
```

### Step 4: Push to GitHub

```bash
# Push to main branch
git push -u origin main

# Or if using master branch
git push -u origin master
```

If you encounter an error about the branch name, you can set it:

```bash
# Rename branch to main
git branch -M main

# Then push
git push -u origin main
```

## Daily Workflow

### Making Changes

```bash
# 1. Check current status
git status

# 2. See what changed
git diff

# 3. Add changed files
git add <filename>
# or add all changes
git add .

# 4. Commit with message
git commit -m "Description of changes"

# 5. Push to GitHub
git push
```

### Example: Adding a New Feature

```bash
# Create a new branch for the feature
git checkout -b feature/new-dashboard

# Make your changes...
# Edit files, add new features

# Check what changed
git status
git diff

# Add and commit
git add .
git commit -m "Add new dashboard feature"

# Push the branch
git push -u origin feature/new-dashboard

# Merge back to main (after testing)
git checkout main
git merge feature/new-dashboard
git push
```

## Common Git Commands

### Checking Status

```bash
# See current status
git status

# See commit history
git log

# See commit history (one line per commit)
git log --oneline

# See last 5 commits
git log -5

# See changes in files
git diff

# See changes in staged files
git diff --staged
```

### Adding Files

```bash
# Add specific file
git add filename.php

# Add all files in a folder
git add api/

# Add all PHP files
git add *.php

# Add all files
git add .

# Add all files (including deleted)
git add -A
```

### Committing Changes

```bash
# Commit with message
git commit -m "Your message here"

# Commit with detailed message
git commit -m "Short description" -m "Detailed explanation of changes"

# Add and commit in one step
git commit -am "Your message"
```

### Pushing Changes

```bash
# Push to current branch
git push

# Push to specific branch
git push origin main

# Push all branches
git push --all

# Force push (use with caution!)
git push -f
```

### Pulling Changes

```bash
# Pull latest changes
git pull

# Pull from specific branch
git pull origin main

# Pull and rebase
git pull --rebase
```

### Branching

```bash
# List all branches
git branch

# Create new branch
git branch feature-name

# Switch to branch
git checkout feature-name

# Create and switch to new branch
git checkout -b feature-name

# Delete branch
git branch -d feature-name

# Delete remote branch
git push origin --delete feature-name
```

### Undoing Changes

```bash
# Discard changes in file (not staged)
git checkout -- filename.php

# Unstage file (keep changes)
git reset HEAD filename.php

# Undo last commit (keep changes)
git reset --soft HEAD~1

# Undo last commit (discard changes)
git reset --hard HEAD~1

# Revert a commit (creates new commit)
git revert <commit-hash>
```

### Viewing History

```bash
# See commit history
git log

# See commit history with graph
git log --graph --oneline --all

# See changes in a commit
git show <commit-hash>

# See who changed what in a file
git blame filename.php
```

## Recommended Commit Messages

### Format

```
<type>(<scope>): <subject>

<body>

<footer>
```

### Types

- **feat**: New feature
- **fix**: Bug fix
- **docs**: Documentation changes
- **style**: Code style changes (formatting, etc.)
- **refactor**: Code refactoring
- **test**: Adding or updating tests
- **chore**: Maintenance tasks

### Examples

```bash
# Feature
git commit -m "feat(dashboard): Add real-time KPI updates"

# Bug fix
git commit -m "fix(training): Fix department dropdown not loading"

# Documentation
git commit -m "docs(readme): Update installation instructions"

# Style
git commit -m "style(css): Improve dashboard layout"

# Refactor
git commit -m "refactor(api): Optimize KPI calculation queries"

# Test
git commit -m "test(kpi): Add unit tests for KPI calculations"

# Chore
git commit -m "chore(deps): Update Chart.js to v4.4.0"
```

## Branching Strategy

### Main Branches

- **main** (or master): Production-ready code
- **develop**: Development branch

### Feature Branches

```bash
# Create feature branch from develop
git checkout develop
git checkout -b feature/predictive-alerts

# Work on feature...
git add .
git commit -m "feat: Add predictive alerts feature"

# Push feature branch
git push -u origin feature/predictive-alerts

# Merge back to develop
git checkout develop
git merge feature/predictive-alerts
git push
```

### Hotfix Branches

```bash
# Create hotfix branch from main
git checkout main
git checkout -b hotfix/login-bug

# Fix the bug...
git add .
git commit -m "fix: Resolve login authentication issue"

# Merge to main
git checkout main
git merge hotfix/login-bug
git push

# Also merge to develop
git checkout develop
git merge hotfix/login-bug
git push
```

## .gitignore Best Practices

Your `.gitignore` file should exclude:

```
# Generated files
database/kpi_system_complete.sql
database/kpi_system_backup_*.sql

# IDE files
.vscode/
.idea/

# OS files
.DS_Store
Thumbs.db

# Logs
*.log

# Temporary files
*.tmp
*.cache

# Sensitive files (if any)
# config/database.php (uncomment if needed)
```

## Collaboration Workflow

### Cloning the Repository

```bash
# Clone the repository
git clone https://github.com/ErnestEZY/kpi_monitoring_system.git

# Navigate to directory
cd kpi_monitoring_system

# Check remote
git remote -v
```

### Working with Team Members

```bash
# 1. Pull latest changes before starting work
git pull

# 2. Create your feature branch
git checkout -b feature/your-feature

# 3. Make changes and commit
git add .
git commit -m "feat: Your feature description"

# 4. Push your branch
git push -u origin feature/your-feature

# 5. Create Pull Request on GitHub
# (Done via GitHub web interface)

# 6. After PR is merged, update your local main
git checkout main
git pull
```

## Handling Merge Conflicts

```bash
# If you encounter merge conflicts
git pull
# CONFLICT message appears

# 1. Open conflicted files
# Look for conflict markers:
# <<<<<<< HEAD
# Your changes
# =======
# Their changes
# >>>>>>> branch-name

# 2. Resolve conflicts manually
# Edit the file to keep desired changes

# 3. Mark as resolved
git add <conflicted-file>

# 4. Complete the merge
git commit -m "Merge: Resolve conflicts"

# 5. Push
git push
```

## GitHub-Specific Features

### Creating a Release

```bash
# Tag a version
git tag -a v1.0.0 -m "Version 1.0.0: Initial release"

# Push tags
git push --tags

# Create release on GitHub
# (Done via GitHub web interface)
```

### Pull Requests

1. Push your branch to GitHub
2. Go to GitHub repository
3. Click "Pull Request"
4. Select your branch
5. Add description
6. Submit for review

### Issues

- Use GitHub Issues to track bugs and features
- Reference issues in commits: `fix: Resolve login bug (#123)`

## Best Practices

### Commit Often

```bash
# Good: Small, focused commits
git commit -m "feat: Add login form validation"
git commit -m "style: Improve login page layout"
git commit -m "fix: Resolve password validation bug"

# Bad: Large, unfocused commit
git commit -m "Update login page"
```

### Write Clear Commit Messages

```bash
# Good
git commit -m "fix(training): Fix department dropdown not populating

- Added loadDepartments() function
- Implemented fallback department list
- Added error handling and logging"

# Bad
git commit -m "fixed stuff"
```

### Keep Commits Atomic

- One commit = one logical change
- Don't mix unrelated changes
- Makes it easier to revert if needed

### Pull Before Push

```bash
# Always pull before pushing
git pull
git push
```

### Use Branches

```bash
# Don't work directly on main
git checkout -b feature/my-feature

# Work on feature...
git commit -m "feat: Add my feature"

# Merge when ready
git checkout main
git merge feature/my-feature
```

## Troubleshooting

### Authentication Issues

```bash
# If using HTTPS, you may need a personal access token
# Generate token on GitHub: Settings > Developer settings > Personal access tokens

# Use token as password when prompted
```

### Push Rejected

```bash
# If push is rejected, pull first
git pull --rebase
git push
```

### Accidentally Committed Sensitive Data

```bash
# Remove file from git but keep locally
git rm --cached config/database.php

# Add to .gitignore
echo "config/database.php" >> .gitignore

# Commit
git commit -m "chore: Remove sensitive file from git"
git push
```

### Reset to Remote State

```bash
# Discard all local changes and match remote
git fetch origin
git reset --hard origin/main
```

## Quick Reference

### Essential Commands

```bash
# Status
git status

# Add all changes
git add .

# Commit
git commit -m "message"

# Push
git push

# Pull
git pull

# Create branch
git checkout -b branch-name

# Switch branch
git checkout branch-name

# Merge branch
git merge branch-name

# View history
git log --oneline
```

## Summary

### First Time Setup

```bash
git init
git remote add origin https://github.com/ErnestEZY/kpi_monitoring_system.git
git add .
git commit -m "Initial commit: Complete KPI Monitoring System v1.0"
git branch -M main
git push -u origin main
```

### Daily Workflow

```bash
git pull                          # Get latest changes
# Make your changes...
git status                        # Check what changed
git add .                         # Stage changes
git commit -m "Description"       # Commit changes
git push                          # Push to GitHub
```

### Feature Development

```bash
git checkout -b feature/name      # Create feature branch
# Develop feature...
git add .
git commit -m "feat: Description"
git push -u origin feature/name
# Create Pull Request on GitHub
```

---

**For more information:**
- [Git Documentation](https://git-scm.com/doc)
- [GitHub Guides](https://guides.github.com/)
- [Git Cheat Sheet](https://education.github.com/git-cheat-sheet-education.pdf)
