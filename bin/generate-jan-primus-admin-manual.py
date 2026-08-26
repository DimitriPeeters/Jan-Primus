from __future__ import annotations

from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import Image, ListFlowable, ListItem, PageBreak, Paragraph, SimpleDocTemplate, Spacer, Table, TableStyle


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "public" / "assets" / "docs" / "jan-primus-adminhandleiding.pdf"
LOGO = ROOT / "public" / "assets" / "images" / "jan-primus-logo.png"
ORANGE = colors.HexColor("#EF6012")
ORANGE_DARK = colors.HexColor("#A63D06")
ORANGE_SOFT = colors.HexColor("#FFF2E8")
INK = colors.HexColor("#211711")
MUTED = colors.HexColor("#76665C")
PALE = colors.HexColor("#F7F4F1")
GREEN = colors.HexColor("#166534")
PALE_GREEN = colors.HexColor("#F0FDF4")
AMBER = colors.HexColor("#92400E")
PALE_AMBER = colors.HexColor("#FFFBEB")
WHITE = colors.white


def register_fonts() -> tuple[str, str]:
    regular = next(path for path in [Path("C:/Windows/Fonts/arial.ttf"), Path("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf")] if path.is_file())
    bold = next(path for path in [Path("C:/Windows/Fonts/arialbd.ttf"), Path("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf")] if path.is_file())
    pdfmetrics.registerFont(TTFont("JP-Regular", str(regular)))
    pdfmetrics.registerFont(TTFont("JP-Bold", str(bold)))
    return "JP-Regular", "JP-Bold"


FONT, FONT_BOLD = register_fonts()
SAMPLE = getSampleStyleSheet()
STYLES = {
    "title": ParagraphStyle("JPTitle", parent=SAMPLE["Title"], fontName=FONT_BOLD, fontSize=27, leading=31, textColor=WHITE, alignment=TA_LEFT),
    "subtitle": ParagraphStyle("JPSubtitle", parent=SAMPLE["Normal"], fontName=FONT, fontSize=12, leading=17, textColor=colors.HexColor("#FFF2E8")),
    "kicker": ParagraphStyle("JPKicker", parent=SAMPLE["Normal"], fontName=FONT_BOLD, fontSize=8, leading=10, textColor=colors.HexColor("#FFE0C7"), spaceAfter=8),
    "h1": ParagraphStyle("JPH1", parent=SAMPLE["Heading1"], fontName=FONT_BOLD, fontSize=19, leading=23, textColor=INK, spaceAfter=9),
    "h2": ParagraphStyle("JPH2", parent=SAMPLE["Heading2"], fontName=FONT_BOLD, fontSize=12.5, leading=16, textColor=ORANGE_DARK, spaceBefore=10, spaceAfter=5),
    "body": ParagraphStyle("JPBody", parent=SAMPLE["BodyText"], fontName=FONT, fontSize=9.2, leading=13.2, textColor=INK, spaceAfter=6),
    "small": ParagraphStyle("JPSmall", parent=SAMPLE["BodyText"], fontName=FONT, fontSize=8, leading=11, textColor=MUTED),
    "table_head": ParagraphStyle("JPTableHead", parent=SAMPLE["BodyText"], fontName=FONT_BOLD, fontSize=8.3, leading=10.5, textColor=WHITE),
    "table": ParagraphStyle("JPTable", parent=SAMPLE["BodyText"], fontName=FONT, fontSize=8.2, leading=11.2, textColor=INK),
    "callout": ParagraphStyle("JPCallout", parent=SAMPLE["BodyText"], fontName=FONT, fontSize=8.7, leading=12.2, textColor=INK),
    "cover_meta": ParagraphStyle("JPCoverMeta", parent=SAMPLE["BodyText"], fontName=FONT, fontSize=9, leading=13, textColor=MUTED, alignment=TA_CENTER),
}


def p(text: str, style: str = "body") -> Paragraph:
    return Paragraph(text, STYLES[style])


def bullets(items: list[str]) -> ListFlowable:
    return ListFlowable([ListItem(p(item), leftIndent=4) for item in items], bulletType="bullet", bulletFontName=FONT_BOLD, bulletFontSize=7, bulletColor=ORANGE, leftIndent=15, bulletOffsetY=1, spaceAfter=5)


def callout(title: str, text: str, kind: str = "info") -> Table:
    background, accent = {"info": (ORANGE_SOFT, ORANGE), "safe": (PALE_GREEN, GREEN), "warn": (PALE_AMBER, AMBER)}[kind]
    table = Table([[p(f"<b>{title}</b><br/>{text}", "callout")]], colWidths=[166 * mm])
    table.setStyle(TableStyle([("BACKGROUND", (0, 0), (-1, -1), background), ("BOX", (0, 0), (-1, -1), .5, accent), ("LINEBEFORE", (0, 0), (0, -1), 3, accent), ("LEFTPADDING", (0, 0), (-1, -1), 11), ("RIGHTPADDING", (0, 0), (-1, -1), 11), ("TOPPADDING", (0, 0), (-1, -1), 9), ("BOTTOMPADDING", (0, 0), (-1, -1), 9)]))
    return table


def process_table(rows: list[tuple[str, str]]) -> Table:
    data = [[p("Stap", "table_head"), p("Wat doe je?", "table_head")]] + [[p(a, "table"), p(b, "table")] for a, b in rows]
    table = Table(data, colWidths=[34 * mm, 132 * mm], repeatRows=1)
    table.setStyle(TableStyle([("BACKGROUND", (0, 0), (-1, 0), INK), ("ROWBACKGROUNDS", (0, 1), (-1, -1), [WHITE, PALE]), ("BOX", (0, 0), (-1, -1), .5, colors.HexColor("#DED6D0")), ("INNERGRID", (0, 0), (-1, -1), .35, colors.HexColor("#DED6D0")), ("VALIGN", (0, 0), (-1, -1), "TOP"), ("LEFTPADDING", (0, 0), (-1, -1), 7), ("RIGHTPADDING", (0, 0), (-1, -1), 7), ("TOPPADDING", (0, 0), (-1, -1), 6), ("BOTTOMPADDING", (0, 0), (-1, -1), 6)]))
    return table


def header(canvas, doc) -> None:
    canvas.saveState()
    width, height = A4
    canvas.setFillColor(INK); canvas.rect(0, height - 14 * mm, width, 14 * mm, fill=1, stroke=0)
    canvas.setFillColor(ORANGE); canvas.rect(0, height - 14 * mm, 54 * mm, 14 * mm, fill=1, stroke=0)
    canvas.setFillColor(WHITE); canvas.setFont(FONT_BOLD, 9); canvas.drawString(14 * mm, height - 9.2 * mm, "vzw Jan Primus")
    canvas.setFont(FONT, 8); canvas.drawRightString(width - 14 * mm, height - 9.2 * mm, "Adminhandleiding - testversie")
    canvas.setStrokeColor(colors.HexColor("#DED6D0")); canvas.line(14 * mm, 13 * mm, width - 14 * mm, 13 * mm)
    canvas.setFillColor(MUTED); canvas.setFont(FONT, 7.5); canvas.drawString(14 * mm, 8.5 * mm, "Ledenbeheer - intern gebruik")
    canvas.drawRightString(width - 14 * mm, 8.5 * mm, f"Pagina {doc.page}"); canvas.restoreState()


def cover_footer(canvas, doc) -> None:
    canvas.saveState(); width, _ = A4
    canvas.setStrokeColor(colors.HexColor("#DED6D0")); canvas.line(14 * mm, 13 * mm, width - 14 * mm, 13 * mm)
    canvas.setFillColor(MUTED); canvas.setFont(FONT, 7.5); canvas.drawCentredString(width / 2, 8.5 * mm, "Versie 0.2 - 26/08/2026 - intern gebruik"); canvas.restoreState()


def section(story: list, number: str, title: str, intro: str) -> None:
    story.extend([p(f"{number}  {title}", "h1"), p(intro)])


def build_manual() -> None:
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    doc = SimpleDocTemplate(str(OUTPUT), pagesize=A4, leftMargin=22 * mm, rightMargin=22 * mm, topMargin=23 * mm, bottomMargin=19 * mm, title="Jan Primus Ledenbeheer - adminhandleiding", author="vzw Jan Primus", subject="Beheerdershandleiding voor de testversie")
    story: list = []
    logo = Image(str(LOGO), width=31 * mm, height=31 * mm) if LOGO.is_file() else Spacer(1, 1)
    cover = Table([[logo, p("ADMINHANDLEIDING - TESTVERSIE", "kicker")], ["", p("Ledenbeheer", "title")], ["", p("Praktische gids voor bestuurders van vzw Jan Primus", "subtitle")]], colWidths=[40 * mm, 126 * mm])
    cover.setStyle(TableStyle([("BACKGROUND", (0, 0), (-1, -1), ORANGE), ("SPAN", (0, 0), (0, 2)), ("VALIGN", (0, 0), (-1, -1), "MIDDLE"), ("LEFTPADDING", (0, 0), (-1, -1), 15), ("RIGHTPADDING", (0, 0), (-1, -1), 15), ("TOPPADDING", (0, 0), (-1, 0), 17), ("BOTTOMPADDING", (0, 2), (-1, 2), 18)]))
    story += [Spacer(1, 18 * mm), cover, Spacer(1, 14 * mm), p("Deze handleiding volgt de actuele online testversie op leden.jan-primus.be. Ze beschrijft de beheertaken voor registraties, leden, evenementen, eventdagen, shifts, mailings, rapporten en instellingen.<br/><br/>Versie 0.2 - 26/08/2026", "cover_meta"), Spacer(1, 9 * mm), callout("Testafspraak", "Gebruik de testfase om de volledige flow te doorlopen. Publiceer alleen een testevenement wanneer de uitnodigingsmail werkelijk naar alle actieve leden mag vertrekken.", "warn"), Spacer(1, 8 * mm), process_table([("1", "Laat een bestuurslid zichzelf publiek registreren."), ("2", "Beoordeel en activeer het account als administrator."), ("3", "Maak een testevent met dagen, functies en eventueel gelijktijdige shifts."), ("4", "Test zowel de keuze door het lid als een toewijzing of override door de administrator."), ("5", "Controleer automatische en manuele mails en noteer feedback.")]), PageBreak()]

    section(story, "1", "Aanmelden en dashboard", "Het dashboard toont het belangrijkste openstaande beheerwerk. Alleen administrators zien de volledige beheeromgeving.")
    story.append(process_table([("Wachtende accounts", "Open Gebruikers en beoordeel nieuwe publieke registraties."), ("Eventinschrijvingen", "Open het evenement en beoordeel deelname waar nodig."), ("Annulatieaanvragen", "Controleer aanvragen van leden met actieve shifttoewijzingen."), ("Komende events", "Controleer publicatiestatus, dagen, shifts en capaciteit."), ("Open shifts", "Bekijk waar nog medewerkers nodig zijn.")]))
    story.append(p("Veilig werken", "h2")); story.append(bullets(["Gebruik een persoonlijk beheerdersaccount en deel geen wachtwoorden.", "Maak alleen bestuursleden met beheertaken administrator.", "Meld af op gedeelde toestellen.", "Zet geen nationaal identificatienummer, wachtwoord of token in screenshots of e-mails."]))
    story.append(PageBreak())

    section(story, "2", "Publieke registratie en goedkeuring", "Een administrator maakt geen nieuw lid aan. Ieder lid registreert zichzelf via de publieke registratiepagina; daarna beoordeelt een administrator het account.")
    story.append(process_table([("Registreren", "Het lid opent https://leden.jan-primus.be/register en vult het volledige formulier in."), ("Wachten", "Het account kan nog niet aanmelden zolang het niet is goedgekeurd."), ("Controleren", "Ga naar Gebruikers, open de registratie en controleer identiteit, e-mailadres en profielgegevens."), ("Goedkeuren", "Activeer betrouwbare registraties. Het gekoppelde ledenprofiel wordt actief."), ("Rol", "Behoud normaal de rol lid. Kies admin uitsluitend voor een bestuurder die de applicatie beheert."), ("Afwijzen", "Activeer een onvolledige of verdachte registratie niet; verifieer eerst buiten het systeem.")]))
    story.append(callout("Eén geheel", "Ieder lid heeft precies één gekoppeld gebruikersaccount. De rollen zijn <b>admin</b> en <b>lid</b>; er zijn geen groepen.", "safe"))
    story.append(PageBreak())

    section(story, "3", "Leden en persoonsgegevens", "Het ledenprofiel bevat de gegevens die Jan Primus nodig heeft voor contact, verzekering en praktische organisatie.")
    story.append(bullets(["naam, voornaam, e-mailadres en telefoonnummer;", "adresgegevens;", "geboortedatum en verplicht nationaal identificatienummer voor de verzekering;", "geslacht en T-shirtmaat;", "lidstatus en mailvoorkeuren."]))
    story.append(p("Wijzigen", "h2")); story.append(process_table([("Zoeken", "Ga naar Leden en zoek op naam of contactgegevens."), ("Bekijken", "Controleer profiel, status en accountkoppeling."), ("Wijzigen", "Corrigeer alleen verifieerbare gegevens en sla op."), ("Controle", "Open het profiel opnieuw en controleer de gewijzigde velden.")]))
    story.append(callout("Gevoelig gegeven", "Het nationaal identificatienummer wordt versleuteld opgeslagen. Gebruik dit alleen voor het verzekeringsdoel en neem het nooit op in mailings of exports zonder expliciete noodzaak.", "warn"))
    story.append(PageBreak())

    section(story, "4", "Evenementen en eventdagen", "Een administrator maakt een evenement eerst als concept. Leden kunnen zich pas registreren zodra het evenement gepubliceerd is.")
    story.append(process_table([("Nieuw event", "Vul titel, periode, locatie, capaciteit en beschrijving in."), ("Eventdagen", "Controleer alle beschikbare dagen. Een lid kan één of meer dagen kiezen."), ("Shifts", "Voeg per dag de nodige functies, locaties, begin- en einduren en capaciteit toe."), ("Concept", "Sla eerst als concept op en voer een volledige controle uit."), ("Publiceren", "Wijzig naar gepubliceerd wanneer alle actieve leden de uitnodigingsmail mogen ontvangen."), ("Wijzigen", "Informeer deelnemers wanneer een gepubliceerde planning betekenisvol verandert.")]))
    story.append(callout("Automatische publicatiemail", "De overgang naar <b>gepubliceerd</b> plant voor elk actief lid een persoonlijke mail in. Die mail vermeldt dat het lid één of meerdere eventdagen en één of meerdere shifts kan kiezen.", "warn"))
    story.append(PageBreak())

    section(story, "5", "Eventinschrijvingen", "Met een eventinschrijving bedoelen we dat het lid zichzelf voor het evenement en één of meerdere eventdagen geregistreerd heeft.")
    story.append(process_table([("Inschrijving", "Het lid kiest een gepubliceerd evenement en één of meer eventdagen."), ("Beoordelen", "Controleer op de eventdetailpagina de deelname en gekozen dagen."), ("Bevestigd", "Het lid mag voor de bevestigde dag(en) beschikbare shifts kiezen."), ("Reserve", "Gebruik reserve wanneer deelname nog niet definitief kan worden bevestigd."), ("Geweigerd", "Gebruik wanneer deelname niet kan worden toegestaan."), ("Annulatie", "Beoordeel een annulatieaanvraag zorgvuldig wanneer het lid al op shifts staat.")]))
    story.append(PageBreak())

    section(story, "6", "Shifts kiezen en toewijzen", "Zowel het lid als de administrator speelt een rol. Een geregistreerd lid kan gepubliceerde shifts kiezen; de administrator kan ook zelf toewijzen en behoudt het laatste woord.")
    story.append(process_table([("Lid kiest", "Het lid ziet de beschikbare shifts op de eventdagen waarvoor het geregistreerd is."), ("Admin wijst toe", "De administrator kan een geschikt geregistreerd lid rechtstreeks aan een shift koppelen."), ("Beoordelen", "Controleer capaciteit, functie, locatie en eventuele andere shiftkeuzes."), ("Override", "De administrator kan de keuze aanpassen, verplaatsen, bevestigen, op reserve zetten of annuleren."), ("Wijziging", "Wanneer shiftgegevens wijzigen, krijgen alle leden op die shift automatisch de bijgewerkte informatie."), ("Planning", "Controleer het uiteindelijke persoonlijke shiftoverzicht van het lid.")]))
    story.append(callout("Geen overlap", "Een medewerker kan meerdere shifts op dezelfde dag uitvoeren, maar nooit twee shifts waarvan de tijden overlappen. Dit geldt ook bij verschillende functies of locaties en wordt door het systeem gecontroleerd.", "safe"))
    story.append(p("Nachtshift", "h2")); story.append(p("Een eindtijd vóór de starttijd hoort bij de volgende kalenderdag. Controleer daarom altijd de volledige datum en het uur op de detailpagina."))
    story.append(PageBreak())

    section(story, "7", "Mailings", "Alle mails gaan eerst naar een databasewachtrij. De beveiligde Jan Primus-mailworker verwerkt die wachtrij automatisch en houdt rekening met de verzendlimieten van One.com.")
    story.append(p("Automatische mails", "h2")); story.append(bullets(["uitnodiging wanneer een evenement gepubliceerd wordt;", "beslissing over een eventinschrijving;", "annulatie van een evenement of deelname;", "persoonlijke shiftplanning;", "update wanneer een shift gewijzigd wordt;", "wachtwoordherstel."]))
    story.append(p("Manuele mailing", "h2")); story.append(process_table([("Doelgroep", "Kies alle actieve leden, leden op een event, leden op één shift of leden op meerdere geselecteerde shifts."), ("Inhoud", "Schrijf onderwerp en bericht. De persoonlijke begroeting en vaste afsluiting worden automatisch toegevoegd."), ("Bijlage", "Voeg indien nodig één toegestane bijlage toe binnen de ingestelde limiet."), ("Inplannen", "Controleer de doelgroep en maak de mailing aan."), ("Opvolgen", "Bekijk ontvangers, wachtrij, pogingen, verzonden aantallen en fouten in Mailings.")]))
    story.append(callout("Wachtrij", "De browser hoeft niet open te blijven. Blijft een aflevering op <b>In wachtrij</b> met 0 pogingen staan, controleer dan de Jan Primus-cronjob. Bij <b>Mislukt</b> lees je eerst de foutmelding.", "info"))
    story.append(PageBreak())

    section(story, "8", "Rapporten en aanwezigheid", "Rapporten ondersteunen de praktische eventopvolging. Jan Primus doet geen uitbetalingen aan leden; IBAN en vergoedingsrapportering maken daarom geen deel uit van deze toepassing.")
    story.append(process_table([("Per shift", "Selecteer één shift en registreer de aanwezigheid van iedere bevestigde medewerker."), ("Per dag", "Selecteer een datum. Ieder lid staat alfabetisch op achternaam één keer in de lijst, met afzonderlijke vakjes voor alle shifts van die dag."), ("Walkie", "Vul optioneel een nummer van maximaal 10 karakters in. Dit wordt per lid en dag bewaard."), ("Oortje", "Vink optioneel aan of het lid die dag een oortje heeft ontvangen."), ("Per event", "Selecteer een evenement voor een afdrukbare alfabetische lijst van alle unieke, actueel ingeschreven leden en hun gekozen dagen."), ("Nakijken", "Vergelijk de lijst met de werkelijke planning en corrigeer fouten."), ("Privacy", "Bewaar of deel rapporten alleen zolang en met wie dit voor de organisatie nodig is.")]))
    story.append(PageBreak())

    section(story, "9", "Instellingen en testchecklist", "De instellingenpagina bevat de organisatienaam, mailafzender, reply-adres, operationele status en het beheer van shiftfuncties.")
    story.append(bullets(["Controleer dat applicatienaam <b>Ledenbeheer</b> en organisatie <b>vzw Jan Primus</b> zijn.", "Gebruik medewerkers@jan-primus.be als afzender en info@jan-primus.be als reply-adres.", "Beheer functies volgens de werkelijke noden; er is geen verplichte standaardfunctie Steward.", "Wijzig nooit mailboxwachtwoorden, databasetoegang of worker-token in de webinterface."]))
    story.append(p("Testchecklist", "h2")); story.append(process_table([("Registratie", "Publiek registreren, goedkeuren en aanmelden als lid."), ("Rollen", "Controleren dat een lid geen beheerschermen kan openen en een admin wel."), ("Event", "Concept maken, dagen en shifts toevoegen en publiceren."), ("Shiftkeuze", "Kiezen als lid, toewijzen en overrulen als administrator en overlap proberen."), ("Mail", "Publicatie-, shiftupdate- en manuele mail controleren in Outlook en in de wachtrij."), ("Mobiel", "Belangrijkste flows ook op telefoon of tablet uitvoeren."), ("Feedback", "Noteer pagina, handeling, verwacht resultaat en werkelijk resultaat zonder gevoelige gegevens.")]))
    story.append(Spacer(1, 5 * mm)); story.append(callout("Tijdens de bestuursproef", "De toepassing is nog niet via een knop op de publieke WordPress-website bereikbaar. Deel rechtstreeks https://leden.jan-primus.be en voeg de publieke knop pas toe nadat de testfeedback verwerkt is.", "safe"))

    doc.build(story, onFirstPage=header, onLaterPages=header)
    print(f"Adminhandleiding aangemaakt: {OUTPUT}")


if __name__ == "__main__":
    build_manual()
