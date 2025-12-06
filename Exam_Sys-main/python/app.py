import os
from flask import Flask, request, redirect, render_template_string
import mysql.connector
from mysql.connector import errorcode
from werkzeug.security import generate_password_hash

app = Flask(__name__)

# --- DB CONFIG (same logic as your PHP) ---

HOST = os.getenv("MYSQLHOST", "shortline.proxy.rlwy.net")
PORT = int(os.getenv("MYSQLPORT", "31347"))
USER = os.getenv("MYSQLUSER", "root")
PASSWORD = os.getenv("MYSQLPASSWORD", "your_mysql_password_here")
DATABASE = os.getenv("MYSQLDATABASE", "railway")

if not HOST or not USER or not DATABASE:
    raise RuntimeError(
        "Database env vars not found. Are you running on Railway? "
        "Set MYSQLHOST, MYSQLUSER, MYSQLPASSWORD, MYSQLDATABASE."
    )


def get_connection():
    """Open a new DB connection."""
    return mysql.connector.connect(
        host=HOST,
        port=PORT,
        user=USER,
        password=PASSWORD,
        database=DATABASE,
    )


def ensure_students_table():
    """Create the students table if it doesn't exist (MySQL syntax)."""
    create_sql = """
        CREATE TABLE IF NOT EXISTS students (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT,
            first_name VARCHAR(100) NOT NULL,
            last_name  VARCHAR(100) NOT NULL,
            email      VARCHAR(191) NOT NULL,
            student_id VARCHAR(100) NOT NULL,
            password   VARCHAR(255) NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY uq_students_email (email)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
          COLLATE=utf8mb4_unicode_ci;
    """
    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(create_sql)
        conn.commit()
    finally:
        conn.close()


# Make sure table exists on startup
ensure_students_table()


# --- SIMPLE HTML FORM (if you don’t already have one) ---

FORM_HTML = """
<!doctype html>
<html>
  <head><title>Register Student</title></head>
  <body>
    <h1>Register Student</h1>
    {% if error %}
      <p style="color:red;">{{ error }}</p>
    {% endif %}
    <form method="post" action="/register">
      <label>First Name:
        <input type="text" name="first_name">
      </label><br>
      <label>Last Name:
        <input type="text" name="last_name">
      </label><br>
      <label>Email:
        <input type="email" name="email">
      </label><br>
      <label>Student ID:
        <input type="text" name="student_id">
      </label><br>
      <label>Password:
        <input type="password" name="password">
      </label><br>
      <button type="submit">Register</button>
    </form>
  </body>
</html>
"""


# --- ROUTES ---

@app.route("/register", methods=["GET", "POST"])
def register():
    if request.method == "GET":
        # Similar to your "Please submit the form" – or just show form
        return render_template_string(FORM_HTML, error=None)

    # POST: handle submission
    first = (request.form.get("first_name") or "").strip()
    last = (request.form.get("last_name") or "").strip()
    email = (request.form.get("email") or "").strip()
    sid = (request.form.get("student_id") or "").strip()
    raw_pw = request.form.get("password") or ""

    if not (first and last and email and sid and raw_pw):
        return render_template_string(FORM_HTML, error="All fields are required.")

    hashed = generate_password_hash(raw_pw)  # uses Werkzeug (SHA256 + salt)

    insert_sql = """
        INSERT INTO students (first_name, last_name, email, student_id, password)
        VALUES (%s, %s, %s, %s, %s)
    """

    conn = get_connection()
    try:
        with conn.cursor() as cur:
            cur.execute(insert_sql, (first, last, email, sid, hashed))
        conn.commit()
    except mysql.connector.Error as err:
        # MySQL duplicate key error = 1062
        if err.errno == errorcode.ER_DUP_ENTRY:
            return render_template_string(
                FORM_HTML,
                error="Error: That email is already registered.",
            )
        else:
            return f"<h2>Insert error: {err}</h2>", 500
    finally:
        conn.close()

    # Adjust to whatever your view route is
    return redirect("/view")


@app.route("/view")
def view_students():
    """Equivalent to your view.php, very minimal."""
    conn = get_connection()
    try:
        with conn.cursor(dictionary=True) as cur:
            cur.execute("SELECT first_name, last_name, email, student_id FROM students")
            rows = cur.fetchall()
    finally:
        conn.close()

    html_rows = "".join(
        f"<tr><td>{r['first_name']}</td>"
        f"<td>{r['last_name']}</td>"
        f"<td>{r['email']}</td>"
        f"<td>{r['student_id']}</td></tr>"
        for r in rows
    )

    return f"""
    <h1>Students</h1>
    <table border="1" cellpadding="4">
      <tr>
        <th>First</th><th>Last</th><th>Email</th><th>Student ID</th>
      </tr>
      {html_rows}
    </table>
    """


if __name__ == "__main__":
    # For local testing; Railway will set PORT env var
    port = int(os.getenv("PORT", 5000))
    app.run(host="0.0.0.0", port=port, debug=True)
