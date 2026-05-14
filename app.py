from flask import Flask, render_template, request
import requests
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
import json
import os
from apscheduler.schedulers.background import BackgroundScheduler
from dotenv import load_dotenv

load_dotenv()
EMAIL_EXPEDITEUR = os.getenv('EMAIL_EXPEDITEUR')
MOT_DE_PASSE = os.getenv('MOT_DE_PASSE')

FICHIER_CANDIDATS = "candidats.json"

def charger_candidats():
    if os.path.exists(FICHIER_CANDIDATS):
        with open(FICHIER_CANDIDATS, 'r') as f:
            return json.load(f)
    return []

def sauvegarder_candidat(prenom, email, domaine, ville):
    candidats = charger_candidats()
    candidats.append({
        'prenom': prenom,
        'email': email,
        'domaine': domaine,
        'ville': ville
    })
    with open(FICHIER_CANDIDATS, 'w') as f:
        json.dump(candidats, f, indent=4)

SITES = [
    "https://www.emploi.bj/",
    "https://www.jobinbenin.com/",
    "https://www.afriquetravail.com/",
    ]
import cloudscraper
from bs4 import BeautifulSoup
def scraper_offres(domaine, ville):
    offres = []

    for site in SITES:
        try:
            response = requests.get(site, timeout=10)
            soup = BeautifulSoup(response.text, 'html.parser')
            cards = soup.find_all('div', class_='group/card')

            for card in cards:
                titre_tag = card.find('span', class_='align-middle')
                ville_tag = card.find('span', class_='truncate')

                if titre_tag and ville_tag:
                    titre = titre_tag.text.strip()
                    ville_offre = ville_tag.text.strip()

                    if domaine.lower() in titre.lower() and ville.lower() in ville_offre.lower():
                        offres.append({
                            'titre': titre,
                            'ville': ville_offre,
                            'site': site
                        })
        except Exception as e:
            print(f"Erreur sur {site} : {e}")

    return offres

def envoyer_email(destinataire, prenom, domaine, ville, offres):
    try:
        msg = MIMEMultipart()
        msg['From'] = EMAIL_EXPEDITEUR
        msg['To'] = destinataire
        msg['Subject'] = f"Offres d'emploi en {domaine} à {ville}"

        contenu = f"Bonjour {prenom},\n\n"
        contenu += f"Voici les offres d'emploi trouvées en {domaine} à {ville} :\n\n"

        for offre in offres:
            contenu += f"- {offre['titre']} | {offre['ville']} | {offre['site']}\n"

        contenu += "\n\nCordialement, \nJob Alert Bénin"

        msg.attach(MIMEText(contenu, 'plain'))

        with smtplib.SMTP_SSL('smtp.gmail.com', 465) as smtp:
            smtp.login(EMAIL_EXPEDITEUR, MOT_DE_PASSE)
            smtp.sendmail(EMAIL_EXPEDITEUR, destinataire, msg.as_string())
        print(f"Email envoyé à {destinataire}")
        return True
    except Exception as e:
        print(f"Erreu email: {e} ")
        return False
app = Flask(__name__)    

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/subscribe', methods=['POST'])
def suscribe():
    prenom = request.form['prenom']
    email = request.form['email']
    domaine = request.form['domaine']
    ville = request.form['ville']

    sauvegarder_candidat(prenom, email, domaine, ville)

    offres = scraper_offres(domaine, ville)

    if offres:
        envoyer_email(email, prenom, domaine, ville, offres)
        message = f"Merci {prenom} ! Les offres ont été envoyées à {email}."
    else:
        message = f"Merci {prenom} ! Aucune offre trouvée pour {domaine} à {ville} pour le moment."

    return message

def envoyer_alertes_automatiques():
    print("Vérification ds offres en cours...")
    candidats = charger_candidats()

    for candidat in candidats:
        offres = scraper_offres(candidat['domaine'], candidat['ville'])
        if offres:
            envoyer_email(
                candidat['email'],
                candidat['prenom'],
                candidat['domaine'],
                candidat['ville'],
                offres
            )
            print(f"Alerte envoyée à {candidat['email']}")
        else:
            print(f"Aucune offre trouvée pour {candidat['prenom']}")

if __name__ == '__main__':
    scheduler = BackgroundScheduler()
    scheduler.add_job(envoyer_alertes_automatiques, 'interval', hours=24)
    scheduler.start()
    app.run(debug=True)

"""
offres_fictives = [
        {'titre': 'Développeur web', 'ville': 'Cotonou', 'site': 'emploi.bj'},
        {'titre': 'Comptable', 'ville': 'Porto-Novo', 'site': 'emploi.bj'},
        {'titre': 'Développeur Python', 'ville': 'Parakou', 'site': 'emploi.bj'},
        {'titre': 'Infirmier', 'ville': 'Cotonou', 'site': 'jobinbenin.com'},
        {'titre': 'Charge Makerting', 'ville': 'Parakou', 'site': 'jobinbenin.com'},
        {'titre': 'Ingénieur Informatique', 'ville': 'Cotonou', 'site': 'emploi.bj'}
    ]
    offres = [
        offre for offre in offres_fictives
        if domaine.lower() in offre['titre'].lower() and ville.lower() in offre['ville'].lower()
    ]
    return offres
"""