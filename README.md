# COS221_Assignment_5


Connor likes doing README files so he can do that (Edit this as you like).

## Getting started

**Please make sure that you have signed into github in your vs code using your tuks account before doing the following and if you have not used github before and you are using Windows, please ensure you install git first**

1. Cloning the repository:
   - Open your terminal in whatever file directory on you computer you want to save this reposity to and paste the following:
     - `git clone https://github.com/u23608821/COS221_Assignment_5`
     
2. Before making any commits to the github, please make sure that you are using your university github account:
   - Open the project folder in vs code
   - In the terminal, use the following to check that the correct github account is being used:
     - `cd /Enter your file directory to where your project folder is located/COS221_Assignment_5 && git config user.name && git config user.email`
   - The above command should display something like this (With your student number and tuks email) ![image](https://github.com/user-attachments/assets/b36acf7d-a2bc-48ec-929c-f73f7db24dff)
   - If it doesn't display your student number and tuks email addres, please do the following:
     - `git config user.email "Your tuks email"`
     - `git config user.name "Your student number"`
   - After configuring your email and student number, run the command that checks which github account you are using again to make sure that it has been updated
  

## Commiting, Pulling and Pushing to GitHub

**Number 1 rule: PLEASE DO NOT COMMIT, MERGE OR BRANCH DIRECTLY TO THE MASETER/DEV BRANCH**

- The master branch will be our "production ready" branch hence once things are commited to master, it should not be changed
- The dev branch will be for features that are nearly ready to be merged to master. This branch will probably still contain small bugs that with still need to be fixed.

- Please create your own seperate branches to work on and then you may pull the code from which ever other branches you need code from. This will help save us many headaches from merge conflicts:
  - `git pull origin <branch name>`
 
- When commiting and pushing to GitHub, please make sure that you write a commit message stating what changes you made and for what
- Please never merge into a branch directly (unless its between your own personal branches), rather create a pull request.
- Always make sure you pull from github before commiting unless you want to cause yourself tears

## Branches
- When you create a new branch, please pull the dev branch into your branch before you start working on anything. This helps so that conflicts are less frequent and we are all working with the same file structure.
- Please don't work in somebody else's branch if you are not going to clearly communicate with them. It is reccommended that each person has their own branch. 
- If you have merged your branch and no longer need your branch, please delete it so that we don't have random floating branches

