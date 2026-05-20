from flask import Flask, render_template, request
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
import json
import os
import requests
from bs4 import BeautifulSoup
from apscheduler.schedulers.background import BackgroundScheduler
from dotenv import load_dotenv
import psycopg2

load_dotenv()
EMAIL_EXPEDITEUR = os.getenv('EMAIL_EXPEDITEUR')
MOT_DE_PASSE = os.getenv('MOT_DE_PASSE')

FICHIER_CANDIDATS = "candidats.json"

def get_db_connection():
    conn = psycopg2.connect("postgresql://postgres:doSTGzamudgzvOXDXxSALhWeuhCpzZlu@autorack.proxy.rlwy.net:10032/railway")
    return conn

def init_db():
    conn = get_db_connection()
    cur = conn.cursor()
    cur.execute('''
        CREATE TABLE IF NOT EXISTS candidats (
                id SERIAL PRIMARY KEY,
                prenom VARCHAR(100),
                email VARCHAR(100),
                domaine VARCHAR(100)    
            )
    ''')
    conn.commit()
    cur.close()
    conn.close()

def charger_candidats():
    conn = get_db_connection()
    cur = conn.cursor()
    cur.execute('SELECT prenom, email, domaine FROM candidats')
    rows = cur.fetchall()
    cur.close()
    conn.close()
    return [{'prenom': r[0], 'email': r[1], 'domaine': r[2]} for r in rows]

def sauvegarder_candidat(prenom, email, domaine):
    conn = get_db_connection()
    cur = conn.cursor()
    cur.execute(
        'INSERT INTO candidats (prenom, email, domaine) VALUES (%s, %s, %s)', (prenom, email, domaine)
    )
    conn.commit()
    cur.close()
    conn.close()
    print(f"Candidat sauvegardé : {prenom}")

def supprimer_candidat(pre, em, do):
    conn = get_db_connection()
    cur = conn.cursor()
    cur.execute(
        "DELETE FROM candidats WHERE prenom = %s AND email = %s and domaine = %s",
        (pre, em, do)
    )
    conn.commit()
    cur.close()
    conn.close()
    print(f"Candidat supprimé avec succès : {pre}")

SITES = [
    "https://www.emploibenin.com",
    "https://www.wabajob.com",
    "https://offresdemplois.bj/"
]

def scraper_offres(domaine):
    offres = []
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"
    }

    for site in SITES:
        try:
            response = requests.get(site, headers=headers, timeout=10)
            print(f"Status {site} : {response.status_code}")
            soup = BeautifulSoup(response.text, 'html.parser')

            if "emploibenin" in site:
                cards = soup.find_all('div', class_='last-offers-details')
                for card in cards:
                    titre_tag = card.find('h3')
                    if titre_tag:
                        titre = titre_tag.text.strip()
                        lien = titre_tag.find('a')['href'] if titre_tag.find('a') else site
                        if domaine.lower() in titre.lower():
                            offres.append({
                                'titre': titre,
                                'site': lien
                            })

            elif "wabajob" in site:
                cards = soup.find_all('div', class_='group relative h-full')
                print(f"Cartes wabajob: {len(cards)}")
                for card in cards:
                    titre_tag = card.find('h3')
                    if titre_tag:
                        titre = titre_tag.text.strip()
                        lien = titre_tag.find('a')['href'] if titre_tag.find('a') else site
                        if domaine.lower() in titre.lower():
                            offres.append({
                                'titre': titre,
                                'site': lien
                            })
            
            elif "offresdemplois" in site:
                cards = soup.find_all('div', class_='jbs-grid-usrs-thumb')
                print(f"Cartes offres d'emploi: {len(cards)}")
                for card in cards:
                    titre_tag = card.find('h6')
                    if titre_tag:
                        titre = titre_tag.text.strip()
                        lien = titre_tag.find('a')['href'] if titre_tag.find('a') else site
                        if domaine.lower() in titre.lower():
                            offres.append({
                                'titre': titre,
                                'site': lien
                            })

        except Exception as e:
            print(f"Erreur sur {site} : {e}")

    return offres
import sib_api_v3_sdk
from sib_api_v3_sdk.rest import ApiException

def envoyer_email(destinataire, prenom, domaine, offres):
    try:
        api_key = os.getenv('BREVO_API_KEY')
        print(f"Clé Brevo : {api_key[:10] if api_key else 'NON TROUVÉE'}")
        
        configuration = sib_api_v3_sdk.Configuration()
        configuration.api_key['api-key'] = api_key

        api_instance = sib_api_v3_sdk.TransactionalEmailsApi(
            sib_api_v3_sdk.ApiClient(configuration)
        )


        contenu = f"Bonjour {prenom},\n\n"
        contenu += f"Voici les offres d'emploi trouvées en {domaine} :\n\n"

        for offre in offres:
            contenu += f"- {offre['titre']} | {offre['site']}\n"

        contenu += "\n\nCordialement,\nJob Alert Bénin"

        email = sib_api_v3_sdk.SendSmtpEmail(
            to=[{"email": destinataire, "name": prenom}],
            sender={"email": "jobalertbeninbz@gmail.com", "name": "Job Alert Bénin"},
            subject=f"Offres d'emploi en {domaine}",
            text_content=contenu
        )

        api_instance.send_transac_email(email)
        print(f"Email envoyé à {destinataire}")
        return True
    except ApiException as e:
        print(f"Erreur email : {e}")
        return False

app = Flask(__name__)
init_db()

@app.route('/')
def index():
    return render_template('index.html')

import threading
@app.route('/subscribe', methods=['POST'])
def subscribe():
    prenom = request.form['prenom']
    email = request.form['email']
    domaine = request.form['domaine']

    sauvegarder_candidat(prenom, email, domaine)
    try:
        offres = scraper_offres(domaine)
    except Exception as e:
        offres = []
    if offres:
        thread = threading.Thread(
            target=envoyer_email, 
            args=(email, prenom, domaine, offres)
            )
        thread.start()
        message = f"Merci {prenom} ! {len(offres)} offre(s) trouvée(s) en {domaine}. Vous serez alerté par email à {email}."
    else:
        message = f"Merci {prenom} ! Aucune offre trouvée pour le moment. Vous serez alerté dès qu'une offre sera disponible."

    return render_template('success.html', message=message)

@app.route('/logout', methods=['GET', 'POST'])
def logout():
    if request.method == 'GET':
        return render_template('logout.html')
    prenom = request.form['prenom']
    email = request.form['email']
    domaine = request.form['domaine']

    candidats = charger_candidats()
    candidat_trouve = False
    for candidat in candidats:
        if candidat['prenom'] == prenom and candidat['email'] == email and candidat['domaine'] == domaine:
            candidat_trouve = True
            break
    if candidat_trouve:
        supprimer_candidat(prenom, email, domaine)
        message = f"{prenom}, Merci pour votre fidélité !"
    else:
        message = f"Candidat introuvable : {prenom}"
        print(f"Candidat introuvable : {prenom}")
    return render_template('successlogout.html', message=message)
    

def envoyer_alertes_automatiques():
    print("Vérification des offres en cours...")
    candidats = charger_candidats()

    for candidat in candidats:
        offres = scraper_offres(candidat['domaine'])
        if offres:
            envoyer_email(
                candidat['email'],
                candidat['prenom'],
                candidat['domaine'],
                offres
            )
            print(f"Alerte envoyée à {candidat['email']}")
        else:
            print(f"Aucune offre pour {candidat['prenom']}")

if __name__ == '__main__':
    scheduler = BackgroundScheduler()
    scheduler.add_job(envoyer_alertes_automatiques, 'interval', hours=24)
    scheduler.start()
    app.run(debug=True)
