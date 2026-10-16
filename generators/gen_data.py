#!/usr/bin/env python3
"""Datasets for the Data-Anonymization and SQL-Audit labs.
  1) a medical 'public release' CSV + an external voter CSV  -> linkage/re-identification
  2) kpi_audit.db (SQLite) mirroring the Swiss-AI KPI audit    -> practise the audit queries offline
"""
import csv, os, sqlite3, random, datetime
random.seed(1337)
HERE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
OUT  = os.path.join(HERE, "artifacts", "data"); os.makedirs(OUT, exist_ok=True)

# ---------- 1) Re-identification pair ----------
people = [  # name, age, zip, gender, diagnosis
 ("Anna Meier",29,"8001","F","Flu"),("Beat Huber",41,"8004","M","Diabetes"),
 ("Clara Roth",29,"8001","F","Depression"),("David Frei",55,"3011","M","Hypertension"),
 ("Elena Vogt",34,"8001","F","Flu"),("Felix Brun",41,"8004","M","Asthma"),
 ("Gina Stucki",67,"3011","F","Cancer"),("Hans Keller",55,"3011","M","Flu"),
 ("Iris Baumann",22,"6003","F","Anxiety"),("Jonas Widmer",22,"6003","M","Flu"),
]
with open(os.path.join(OUT,"medical_release.csv"),"w",newline="") as f:
    w=csv.writer(f); w.writerow(["record_id","age","zip","gender","diagnosis"])
    for i,(n,a,z,g,d) in enumerate(people,1): w.writerow([i,a,z,g,d])   # names stripped (quasi-ids remain)
with open(os.path.join(OUT,"voter_list.csv"),"w",newline="") as f:
    w=csv.writer(f); w.writerow(["name","age","zip","gender"])
    for n,a,z,g,d in people: w.writerow([n,a,z,g])   # external table with names -> link on age+zip+gender

# ---------- 2) KPI audit SQLite DB ----------
db = os.path.join(OUT,"kpi_audit.db")
if os.path.exists(db): os.remove(db)
c = sqlite3.connect(db); x = c.cursor()
x.executescript("""
CREATE TABLE models(model_id INTEGER PRIMARY KEY, model_name TEXT, model_tier TEXT,
  input_token_cost REAL, output_token_cost REAL, max_context_tokens INTEGER, lifecycle_status TEXT);
CREATE TABLE users(user_id INTEGER PRIMARY KEY, username TEXT, subscription_tier TEXT,
  account_status TEXT, is_internal INTEGER);
CREATE TABLE sessions(session_id INTEGER PRIMARY KEY, user_id INTEGER, model_id INTEGER,
  started_at TEXT, input_tokens INTEGER, output_tokens INTEGER, session_status TEXT);
CREATE TABLE kpi_claims(claim_id INTEGER PRIMARY KEY, kpi_name TEXT, claimed_value REAL,
  unit TEXT, period_start TEXT, period_end TEXT);
""")
x.executemany("INSERT INTO models VALUES(?,?,?,?,?,?,?)",[
 (1,"lite-1","standard",0.00000015,0.0000006,8192,"ga"),
 (2,"flash-2","standard",0.0000005,0.0000015,131072,"ga"),
 (3,"pro-2","pro",0.000003,0.000015,400000,"ga"),
 (4,"ultra-1","ultra",0.000012,0.00006,1000000,"ga"),
 (5,"lite-0","standard",0.0000001,0.0000004,4096,"deprecated"),   # decoy
 (6,"vision-preview","pro",0.000004,0.000018,262144,"preview"),   # decoy (released late)
])
tiers=["free","free","free","plus","pro","enterprise"]
for uid in range(1,201):
    internal = 1 if uid<=5 else 0
    tier = "enterprise" if internal else random.choice(tiers)
    status = random.choice(["active","active","active","churned"])
    x.execute("INSERT INTO users VALUES(?,?,?,?,?)",(uid,f"user{uid}",tier,status,internal))
sid=1
for day in range(1,31):
    d=f"2026-06-{day:02d}"
    for _ in range(random.randint(150,260)):
        uid=random.randint(1,200); mid=random.choices([1,2,3,4,6],weights=[25,25,25,20,5])[0]
        it=random.randint(200,8000); ot=random.randint(200,12000)
        st="completed" if random.random()>0.05 else "error"
        x.execute("INSERT INTO sessions VALUES(?,?,?,?,?,?,?)",(sid,uid,mid,f"{d} 12:00:00",it,ot,st)); sid+=1
# a few sessions OUTSIDE the window (decoy traffic the lab warns about)
for _ in range(80):
    x.execute("INSERT INTO sessions VALUES(?,?,?,?,?,?,?)",(sid,random.randint(1,200),3,"2026-07-03 12:00:00",5000,5000,"completed")); sid+=1
x.executemany("INSERT INTO kpi_claims VALUES(?,?,?,?,?,?)",[
 (1,"daily_active_users",24500,"users","2026-06-01","2026-06-30"),
 (2,"monthly_revenue",480000,"EUR","2026-06-01","2026-06-30"),
 (3,"premium_model_adoption",68,"percent","2026-06-01","2026-06-30"),
 (4,"total_tokens_processed",10000000000,"tokens","2026-06-01","2026-06-30"),
])
c.commit(); c.close()
print("[*] data artifacts: medical_release.csv, voter_list.csv, kpi_audit.db")
