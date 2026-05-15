from flask import Flask, render_template, request
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
import json
import os
import requests
from bs4 import BeautifulSoup
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
    "https://www.emploibenin.com",
    "https://www.benintalents.com",
]

def scraper_offres(domaine, ville):
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
                print(f"Nombre d'offres trouvées sur {site} : {len(cards)}")
                for card in cards:
                    titre_tag = card.find('a')
                    if titre_tag:
                        print(f"Titre: {titre_tag.text.strip()}")
                        titre = titre_tag.text.strip()
                        lien = titre_tag.find('a')['href'] if titre_tag.find('a') else site
                        if domaine.lower() in titre.lower():
                            offres.append({
                                'titre': titre,
                                'ville': ville,
                                'site': lien
                            })

            elif "benintalents" in site:
                cards = soup.find_all('h3', class_='text-lg')
                print(f"Nombre d'offres trouvées sur {site} : {len(cards)}")
                for card in cards:
                    titre_tag = card.find('a')
                    if titre_tag:
                        print(f"Titre: {titre_tag.text.strip()}")
                    titre = card.text.strip()
                    if domaine.lower() in titre.lower():
                        offres.append({
                            'titre': titre,
                            'ville': ville,
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

        contenu += "\n\nCordialement,\nJob Alert Bénin"

        msg.attach(MIMEText(contenu, 'plain'))

        with smtplib.SMTP_SSL('smtp.gmail.com', 465) as smtp:
            smtp.login(EMAIL_EXPEDITEUR, MOT_DE_PASSE)
            smtp.sendmail(EMAIL_EXPEDITEUR, destinataire, msg.as_string())
        print(f"Email envoyé à {destinataire}")
        return True
    except Exception as e:
        print(f"Erreur email : {e}")
        return False

app = Flask(__name__)

@app.route('/')
def index():
    return render_template('index.html')

@app.route('/subscribe', methods=['POST'])
def subscribe():
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
        message = f"Merci {prenom} ! Aucune offre trouvée pour le moment. Vous serez alerté dès qu'une offre sera disponible."

    return message

def envoyer_alertes_automatiques():
    print("Vérification des offres en cours...")
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
            print(f"Aucune offre pour {candidat['prenom']}")

if __name__ == '__main__':
    scheduler = BackgroundScheduler()
    scheduler.add_job(envoyer_alertes_automatiques, 'interval', hours=24)
    scheduler.start()
    app.run(debug=True)
