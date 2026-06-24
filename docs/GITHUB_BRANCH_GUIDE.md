# GitHub Branching & Pushing Guide

This guide explains how to create a new branch, make your changes, and push that specific branch to GitHub instead of the `main` branch.

## 1. Create and switch to a new branch
To create a new branch and switch to it immediately, use the `checkout -b` command. Replace `your-branch-name` with a descriptive name (like `feature/staging-setup` or `fix/database-config`).

```bash
git checkout -b your-branch-name
```
*(If the branch already exists and you just want to switch to it, use `git checkout your-branch-name` without the `-b`)*.

## 2. Make your changes and stage them
After modifying or adding files, you need to tell Git to stage them for a commit. You can add specific files or all of them.

To add all changes:
```bash
git add .
```

## 3. Commit your changes
Save your staged changes with a descriptive message explaining what you did.

```bash
git commit -m "Added staging server setup documentation"
```

## 4. Push the branch to GitHub
Now, push your new branch to the remote repository (`origin`). Since this branch doesn't exist on GitHub yet, you need to use the `-u` (or `--set-upstream`) flag the first time you push.

```bash
git push -u origin your-branch-name
```

> [!TIP]
> **Future pushes on this branch:**
> Because you used the `-u` flag the first time, Git now knows that your local branch is linked to the `your-branch-name` branch on GitHub. For any future updates to this same branch, you can simply type:
> ```bash
> git push
> ```
