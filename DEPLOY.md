# Free deployment of your VaxCluster version: Render (app) + TiDB Cloud (MySQL database)

Both services were free without a credit card when this guide was written. Check their pricing pages before you start, because free tiers change.

## 0. Before you start
- Use **fake patient data only** on a public website.
- Never put database passwords in the code or in Git. They go in Render's "Environment" settings (step 4).

## 1. Put the project on GitHub
1. Create a GitHub account and a new repository (private is fine).
2. Upload the whole project folder, including `Dockerfile`, `requirements.txt`, `docker/`, `render.yaml`.
   (`.dockerignore` keeps `db sql/`, `tools/`, `models/` out of the web image, but keep them in the repository.)

## 2. Create the database (TiDB Cloud)
1. Sign up at tidbcloud.com and create a **Starter** cluster (the free one).
2. Open the cluster's **Connect** dialog and note: **host**, **port** (usually 4000), **username**, and set a **password**.
   Copy the username exactly. It may have a prefix before `.root`.
3. Open the cluster's **SQL Editor** (or connect with DBeaver / MySQL Workbench) and run:
   ```sql
   CREATE DATABASE Covid;
   USE Covid;
   ```
   Then paste the contents of **`db sql/covid_tidb.sql`** (not `covid.sql`: TiDB cannot add AUTO_INCREMENT after a table exists) and run it. In the SQL Editor, click inside it, press Ctrl+A to select everything, then Run. (It is small.)

## 3. Replace the sample passwords (important)
`covid.sql` contains sample doctor logins with plain-text passwords (`password123`, `abc123`, `qwerty`).
On your computer (XAMPP's PHP is fine), run for each user:
```
C:\xampp\php\php.exe tools\hash_password.php "YourStrongPassword" User1
```
It prints an `UPDATE users SET pass = '...' WHERE username = 'User1';` line. Run that line in the TiDB SQL Editor.
Repeat for `User2` and `User3` (or give them strong passwords you will never share).

## 4. Deploy on Render
1. Sign up at render.com and connect your GitHub account.
2. **New > Web Service**, pick your repository. Language: **Docker**. Instance type: **Free**.
   (Or use **New > Blueprint** to read `render.yaml`.)
3. Add these environment variables:

   | Key | Value |
   |---|---|
   | `DB_HOST` | host from TiDB |
   | `DB_PORT` | `4000` |
   | `DB_USER` | username from TiDB |
   | `DB_PASS` | your TiDB password |
   | `DB_NAME` | `Covid` |
   | `DB_SSL` | `1` |

4. Click **Create Web Service**. The first build takes several minutes (it installs Python and scikit-learn).
   When it says "Live", open the URL Render gives you.

## 5. Test checklist
1. The home page loads; switch EN / FR / العربية.
2. Log in with a doctor account.
3. Add a patient (the vaccination location is now a list) and press **Predict side effects**.
4. Report side effects for that patient; check the list and **Export CSV**.

## Troubleshooting
- **"Database connection failed."** Check the six variables. Open the service's **Logs** tab for the real error.
- **Prediction page shows an error or is empty.** Open **Logs**. A Python error mentioning `sklearn` or `numpy` means the pinned versions in `requirements.txt` don't match the models.
- **Very slow first page.** Free services sleep after about 15 minutes without visitors and take about a minute to wake up.
- **Prediction takes long.** The free instance has a tenth of a CPU core, and each prediction starts Python three times. Test it; the paid instance removes the problem.

## Local use is unchanged
Without the `DB_*` variables the app still connects to XAMPP's MySQL (`root`, no password, database `Covid`).
For predictions on your computer, `configuration/app.php` must point to a Python that has scikit-learn installed.
