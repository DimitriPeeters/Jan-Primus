from __future__ import annotations

from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from reportlab.platypus import (
    KeepTogether,
    ListFlowable,
    ListItem,
    PageBreak,
    Paragraph,
    SimpleDocTemplate,
    Spacer,
    Table,
    TableStyle,
)


ROOT = Path(__file__).resolve().parents[1]
OUTPUT = ROOT / "public" / "assets" / "docs" / "aefs-v2-adminhandleiding.pdf"

NAVY = colors.HexColor("#111827")
RED = colors.HexColor("#B5121B")
RED_DARK = colors.HexColor("#8F0E16")
MUTED = colors.HexColor("#5B6577")
PALE = colors.HexColor("#F4F6FA")
PALE_RED = colors.HexColor("#FFF1F2")
GREEN = colors.HexColor("#166534")
PALE_GREEN = colors.HexColor("#F0FDF4")
AMBER = colors.HexColor("#92400E")
PALE_AMBER = colors.HexColor("#FFFBEB")
WHITE = colors.white


def register_fonts() -> tuple[str, str]:
    regular_candidates = [
        Path("C:/Windows/Fonts/arial.ttf"),
        Path("/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"),
    ]
    bold_candidates = [
        Path("C:/Windows/Fonts/arialbd.ttf"),
        Path("/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"),
    ]

    regular = next(path for path in regular_candidates if path.is_file())
    bold = next(path for path in bold_candidates if path.is_file())
    pdfmetrics.registerFont(TTFont("AEFS-Regular", str(regular)))
    pdfmetrics.registerFont(TTFont("AEFS-Bold", str(bold)))
    return "AEFS-Regular", "AEFS-Bold"


FONT, FONT_BOLD = register_fonts()
SAMPLE = getSampleStyleSheet()

STYLES = {
    "title": ParagraphStyle(
        "AEFSTitle",
        parent=SAMPLE["Title"],
        fontName=FONT_BOLD,
        fontSize=27,
        leading=31,
        textColor=WHITE,
        alignment=TA_LEFT,
        spaceAfter=6,
    ),
    "subtitle": ParagraphStyle(
        "AEFSSubtitle",
        parent=SAMPLE["Normal"],
        fontName=FONT,
        fontSize=12,
        leading=17,
        textColor=colors.HexColor("#FEE2E2"),
    ),
    "kicker": ParagraphStyle(
        "AEFSKicker",
        parent=SAMPLE["Normal"],
        fontName=FONT_BOLD,
        fontSize=8,
        leading=10,
        textColor=colors.HexColor("#FCA5A5"),
        spaceAfter=8,
    ),
    "h1": ParagraphStyle(
        "AEFSH1",
        parent=SAMPLE["Heading1"],
        fontName=FONT_BOLD,
        fontSize=19,
        leading=23,
        textColor=NAVY,
        spaceBefore=2,
        spaceAfter=9,
    ),
    "h2": ParagraphStyle(
        "AEFSH2",
        parent=SAMPLE["Heading2"],
        fontName=FONT_BOLD,
        fontSize=12.5,
        leading=16,
        textColor=RED_DARK,
        spaceBefore=10,
        spaceAfter=5,
    ),
    "body": ParagraphStyle(
        "AEFSBody",
        parent=SAMPLE["BodyText"],
        fontName=FONT,
        fontSize=9.2,
        leading=13.2,
        textColor=NAVY,
        spaceAfter=6,
    ),
    "small": ParagraphStyle(
        "AEFSSmall",
        parent=SAMPLE["BodyText"],
        fontName=FONT,
        fontSize=8,
        leading=11,
        textColor=MUTED,
    ),
    "table_head": ParagraphStyle(
        "AEFSTableHead",
        parent=SAMPLE["BodyText"],
        fontName=FONT_BOLD,
        fontSize=8.3,
        leading=10.5,
        textColor=WHITE,
    ),
    "table": ParagraphStyle(
        "AEFSTable",
        parent=SAMPLE["BodyText"],
        fontName=FONT,
        fontSize=8.2,
        leading=11.2,
        textColor=NAVY,
    ),
    "callout": ParagraphStyle(
        "AEFSCallout",
        parent=SAMPLE["BodyText"],
        fontName=FONT,
        fontSize=8.7,
        leading=12.2,
        textColor=NAVY,
    ),
    "cover_meta": ParagraphStyle(
        "AEFSCoverMeta",
        parent=SAMPLE["BodyText"],
        fontName=FONT,
        fontSize=9,
        leading=13,
        textColor=MUTED,
        alignment=TA_CENTER,
    ),
}


def p(text: str, style: str = "body") -> Paragraph:
    return Paragraph(text, STYLES[style])


def bullets(items: list[str]) -> ListFlowable:
    return ListFlowable(
        [ListItem(p(item), leftIndent=4) for item in items],
        bulletType="bullet",
        bulletFontName=FONT_BOLD,
        bulletFontSize=7,
        bulletColor=RED,
        leftIndent=15,
        bulletOffsetY=1,
        spaceAfter=5,
    )


def callout(title: str, text: str, kind: str = "info") -> Table:
    palette = {
        "info": (PALE, RED),
        "safe": (PALE_GREEN, GREEN),
        "warn": (PALE_AMBER, AMBER),
    }
    background, accent = palette[kind]
    content = p(f"<b>{title}</b><br/>{text}", "callout")
    table = Table([[content]], colWidths=[166 * mm])
    table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), background),
                ("BOX", (0, 0), (-1, -1), 0.5, accent),
                ("LINEBEFORE", (0, 0), (0, -1), 3, accent),
                ("LEFTPADDING", (0, 0), (-1, -1), 11),
                ("RIGHTPADDING", (0, 0), (-1, -1), 11),
                ("TOPPADDING", (0, 0), (-1, -1), 9),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 9),
            ]
        )
    )
    return table


def process_table(rows: list[tuple[str, str]]) -> Table:
    data = [[p("Stap", "table_head"), p("Wat doe je?", "table_head")]]
    data.extend([[p(step, "table"), p(description, "table")] for step, description in rows])
    table = Table(data, colWidths=[31 * mm, 135 * mm], repeatRows=1)
    table.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, 0), NAVY),
                ("ROWBACKGROUNDS", (0, 1), (-1, -1), [WHITE, PALE]),
                ("BOX", (0, 0), (-1, -1), 0.5, colors.HexColor("#D8DEE9")),
                ("INNERGRID", (0, 0), (-1, -1), 0.35, colors.HexColor("#D8DEE9")),
                ("VALIGN", (0, 0), (-1, -1), "TOP"),
                ("LEFTPADDING", (0, 0), (-1, -1), 7),
                ("RIGHTPADDING", (0, 0), (-1, -1), 7),
                ("TOPPADDING", (0, 0), (-1, -1), 6),
                ("BOTTOMPADDING", (0, 0), (-1, -1), 6),
            ]
        )
    )
    return table


def page_header(canvas, doc) -> None:
    canvas.saveState()
    width, height = A4
    canvas.setFillColor(NAVY)
    canvas.rect(0, height - 14 * mm, width, 14 * mm, fill=1, stroke=0)
    canvas.setFillColor(RED)
    canvas.rect(0, height - 14 * mm, 44 * mm, 14 * mm, fill=1, stroke=0)
    canvas.setFillColor(WHITE)
    canvas.setFont(FONT_BOLD, 9)
    canvas.drawString(14 * mm, height - 9.2 * mm, "AEFS v2")
    canvas.setFont(FONT, 8)
    canvas.drawRightString(width - 14 * mm, height - 9.2 * mm, "Adminhandleiding · alfa")
    canvas.setStrokeColor(colors.HexColor("#D8DEE9"))
    canvas.line(14 * mm, 13 * mm, width - 14 * mm, 13 * mm)
    canvas.setFillColor(MUTED)
    canvas.setFont(FONT, 7.5)
    canvas.drawString(14 * mm, 8.5 * mm, "All Events Forever Sure · intern gebruik")
    canvas.drawRightString(width - 14 * mm, 8.5 * mm, f"Pagina {doc.page}")
    canvas.restoreState()


def cover_footer(canvas, doc) -> None:
    canvas.saveState()
    width, _ = A4
    canvas.setStrokeColor(colors.HexColor("#D8DEE9"))
    canvas.line(14 * mm, 13 * mm, width - 14 * mm, 13 * mm)
    canvas.setFillColor(MUTED)
    canvas.setFont(FONT, 7.5)
    canvas.drawCentredString(width / 2, 8.5 * mm, "Versie 0.1 · 17/08/2026 · intern gebruik")
    canvas.restoreState()


def add_section(story: list, number: str, title: str, intro: str) -> None:
    story.append(p(f"{number}  {title}", "h1"))
    story.append(p(intro))


def build_manual() -> None:
    OUTPUT.parent.mkdir(parents=True, exist_ok=True)
    doc = SimpleDocTemplate(
        str(OUTPUT),
        pagesize=A4,
        leftMargin=22 * mm,
        rightMargin=22 * mm,
        topMargin=23 * mm,
        bottomMargin=19 * mm,
        title="AEFS v2 adminhandleiding",
        author="All Events Forever Sure",
        subject="Beheerdershandleiding voor de AEFS v2 alfa",
    )

    story: list = []

    cover = Table(
        [
            [p("ADMINHANDLEIDING · ALFA", "kicker")],
            [p("AEFS v2<br/>Eventbeheer", "title")],
            [p("Praktische gids voor beheerders", "subtitle")],
        ],
        colWidths=[166 * mm],
    )
    cover.setStyle(
        TableStyle(
            [
                ("BACKGROUND", (0, 0), (-1, -1), RED),
                ("LEFTPADDING", (0, 0), (-1, -1), 18),
                ("RIGHTPADDING", (0, 0), (-1, -1), 18),
                ("TOPPADDING", (0, 0), (-1, 0), 18),
                ("TOPPADDING", (0, 1), (-1, 1), 4),
                ("BOTTOMPADDING", (0, 2), (-1, 2), 20),
            ]
        )
    )
    story.extend(
        [
            Spacer(1, 18 * mm),
            cover,
            Spacer(1, 14 * mm),
            p(
                "Deze handleiding volgt de actuele AEFS v2-interface. Ze beschrijft "
                "de beheerflows voor leden, gebruikers, evenementen, shiften, "
                "mailings, rapporten en instellingen.",
                "cover_meta",
            ),
            Spacer(1, 9 * mm),
            callout(
                "Alfa-afspraak",
                "Werk met echte persoonsgegevens alleen wanneer dat nodig is. "
                "Test publicaties, bulkmail en annulaties eerst met een beperkt "
                "testevent en de geconfigureerde mailallowlist.",
                "warn",
            ),
            Spacer(1, 8 * mm),
            process_table(
                [
                    ("1", "Start op het dashboard en verwerk eerst de waarschuwingstegels."),
                    ("2", "Maak evenementen eerst als concept; controleer data en shiften."),
                    ("3", "Publiceer pas wanneer de ledenmail werkelijk mag worden ingepland."),
                    ("4", "Registreer aanwezigheid vóór het vergoedingsrapport wordt gebruikt."),
                ]
            ),
            PageBreak(),
        ]
    )

    add_section(
        story,
        "1",
        "Aanmelden en dashboard",
        "Na aanmelden geeft het dashboard onmiddellijk het operationele werk weer. "
        "De tegels en tabellen zijn links naar de juiste beheerpagina.",
    )
    story.append(
        process_table(
            [
                ("Wachtende accounts", "Open Gebruikers en beoordeel nieuwe registraties."),
                ("Eventinschrijvingen", "Open het evenement en kies bevestigd, reserve of geweigerd."),
                ("Annulatieaanvragen", "Verifieer aanvragen van leden die al op een shift stonden."),
                ("Komende events", "Controleer data, capaciteit en publicatiestatus."),
            ]
        )
    )
    story.append(p("Veilig aanmelden", "h2"))
    story.append(
        bullets(
            [
                "Gebruik een persoonlijk beheerdersaccount; deel geen wachtwoorden.",
                "Gebruik <b>Wachtwoord vergeten</b> als toegang niet meer lukt.",
                "Meld af op een gedeelde laptop of tablet.",
                "Rapporteer onverwachte foutmeldingen met pagina, tijdstip en handeling — nooit met gevoelige persoonsgegevens in een screenshot.",
            ]
        )
    )
    story.append(
        callout(
            "Adminhandleiding",
            "De nieuwste PDF staat als rode kaart rechtsboven op het beheerdersdashboard en opent in een nieuw tabblad.",
            "safe",
        )
    )
    story.append(PageBreak())

    add_section(
        story,
        "2",
        "Leden en groepen",
        "Ledenbeheer bevat contact-, adres-, administratieve en privacygevoelige gegevens. "
        "Alleen beheerders hebben toegang tot de ledenlijst.",
    )
    story.append(p("Lid bekijken of wijzigen", "h2"))
    story.append(
        process_table(
            [
                ("Zoeken", "Ga naar Leden en zoek op naam of contactgegevens."),
                ("Bekijken", "Controleer profiel, status, groepen en administratieve gegevens."),
                ("Wijzigen", "Pas alleen verifieerbare gegevens aan en sla één keer op."),
                ("Controle", "Open het profiel opnieuw en controleer de gewijzigde velden."),
            ]
        )
    )
    story.append(p("Nationaal identificatienummer", "h2"))
    story.append(
        callout(
            "Gevoelig gegeven",
            "Het veld accepteert Belgische en buitenlandse identificatienummers. "
            "AEFS bewaart het versleuteld; een bevoegde beheerder ziet de ontsleutelde "
            "waarde in het profiel. Kopieer die waarde niet naar mails, opmerkingen of exports.",
            "warn",
        )
    )
    story.append(p("Groepen beheren", "h2"))
    story.append(
        bullets(
            [
                "Open <b>Leden → Groepen beheren</b>.",
                "Maak een duidelijke, unieke groepsnaam aan.",
                "Ken leden uitsluitend administratief toe; een lid beheert dit niet zelf.",
                "Een lid behoort historisch maximaal tot één relevante groep voor de huidige rapportering.",
                "Groepen worden gebruikt voor mailselectie en vergoedingsrapporten per vereniging.",
            ]
        )
    )
    story.append(PageBreak())

    add_section(
        story,
        "3",
        "Gebruikers en toegang",
        "Een gebruikersaccount is gekoppeld aan een lid. Nieuwe publieke registraties "
        "blijven geblokkeerd tot een beheerder ze goedkeurt.",
    )
    story.append(
        process_table(
            [
                ("Open", "Ga naar Gebruikers en filter of open het wachtende account."),
                ("Vergelijk", "Controleer het gekoppelde lid en het e-mailadres."),
                ("Rol", "Gebruik alleen de bestaande rollen <b>admin</b> en <b>lid</b>."),
                ("Goedkeuren", "Activeer het account pas wanneer de registratie betrouwbaar is."),
                ("Mailkeuze", "Respecteer de blacklist wanneer een gebruiker geen mail mag ontvangen."),
            ]
        )
    )
    story.append(p("Rolbeheer", "h2"))
    story.append(
        bullets(
            [
                "Maak iemand alleen administrator als die persoon beheerderstaken uitvoert.",
                "Een beheerder kan leden, gebruikers, events, shiften, mailings, rapporten en instellingen beheren.",
                "Een gewoon lid kan zich voor gepubliceerde events registreren maar nooit zelf een shift kiezen.",
                "Controleer na een rolwijziging of de navigatie en toegangsrechten overeenkomen.",
            ]
        )
    )
    story.append(
        callout(
            "Testmailadressen",
            "De lokale alfa gebruikt een allowlist. Nieuwe administrators worden niet automatisch "
            "aan iedere omgeving toegevoegd; beoordeel de lijst per omgeving vóór een mailtest.",
            "info",
        )
    )
    story.append(PageBreak())

    add_section(
        story,
        "4",
        "Evenementen maken en publiceren",
        "Een beheerder maakt het evenement en kan in dezelfde flow meteen shiften toevoegen. "
        "Publicatie is een betekenisvolle statusovergang omdat ze ledenmail inplant.",
    )
    story.append(
        process_table(
            [
                ("Nieuw event", "Vul titel, periode, locatie, capaciteit en beschrijving in."),
                ("Meerdaags", "Controleer start- en einddatum; leden kunnen één of meer dagen kiezen."),
                ("Groepen", "Schakel groepswerking alleen in als dit event per vereniging wordt afgerekend."),
                ("Shiften", "Voeg functie, datum, 24-uurs tijden, capaciteit en vergoeding toe."),
                ("Concept", "Sla eerst als concept op en voer een volledige controle uit."),
                ("Publiceren", "Wijzig naar gepubliceerd wanneer de uitnodigingsmail mag worden ingepland."),
            ]
        )
    )
    story.append(
        callout(
            "Publicatiemail",
            "Alleen de overgang van niet-gepubliceerd naar <b>gepubliceerd</b> maakt één "
            "gepersonaliseerde uitnodiging per geschikt actief lid. Een gewone wijziging aan "
            "een al gepubliceerd event stuurt geen duplicaat.",
            "warn",
        )
    )
    story.append(p("Verwijderen of annuleren", "h2"))
    story.append(
        bullets(
            [
                "Een event zonder inschrijvingshistoriek kan definitief worden verwijderd; lege shiften mogen mee weg.",
                "Zodra event- of shiftinschrijvingen bestaan, blijft de historiek bewaard en gebruik je annuleren.",
                "Bij eventannulatie worden betrokken leden eerst via de mailqueue geïnformeerd; daarna rondt de worker de statustransities af.",
            ]
        )
    )
    story.append(PageBreak())

    add_section(
        story,
        "5",
        "Eventinschrijvingen en annulaties",
        "Leden schrijven zichzelf in voor een gepubliceerd event. Iedere inschrijving start als "
        "wachtend en wordt door een beheerder beoordeeld.",
    )
    story.append(
        process_table(
            [
                ("Wachtend", "Controleer lid, gekozen dagen en beschikbare eventcapaciteit."),
                ("Bevestigd", "Het lid neemt deel en ontvangt automatisch een beslissingsmail."),
                ("Reserve", "Het lid staat op reserve en ontvangt automatisch een reservemail."),
                ("Geweigerd", "Gebruik wanneer deelname niet kan worden toegestaan."),
            ]
        )
    )
    story.append(p("Annulatie door een lid", "h2"))
    story.append(
        process_table(
            [
                ("Geen shift", "De eventinschrijving wordt onmiddellijk ingetrokken."),
                ("Actieve shift", "De aanvraag verschijnt op het dashboard en wacht op verificatie."),
                ("Bevestigen", "AEFS annuleert de actieve shifttoewijzingen coherent en bewaart historiek."),
                ("Opnieuw inschrijven", "Het lid mag later via de normale eventflow opnieuw inschrijven; de bestaande logische inschrijving wordt hergebruikt als wachtend."),
            ]
        )
    )
    story.append(
        callout(
            "Geen beheerdersmail",
            "Individuele annulatieaanvragen blijven bewust als platformmelding op het dashboard. "
            "Er wordt hiervoor geen mail naar alle administrators gestuurd.",
            "info",
        )
    )
    story.append(PageBreak())

    add_section(
        story,
        "6",
        "Shiften en aanwezigheid",
        "Een lid schrijft zich nooit zelf in op een shift. Alleen een beheerder wijst een lid toe "
        "dat voor het juiste event en de kalenderdag bevestigd is.",
    )
    story.append(
        process_table(
            [
                ("Shift", "Controleer functie, volledige start/eindtijd, capaciteit en vergoeding."),
                ("Toewijzen", "Selecteer een geschikt lid en kies bevestigd of reserve."),
                ("Capaciteit", "Alleen bevestigde toewijzingen gebruiken de beschikbare plaatsen."),
                ("Planning", "Gebruik op eventniveau de mailknop om ieder betrokken lid zijn persoonlijke shiftoverzicht te sturen."),
                ("Aanwezig", "Open de shift en gebruik de knop per bevestigde vrijwilliger; de pagina blijft op dezelfde scrollpositie."),
            ]
        )
    )
    story.append(p("Nachtshiften", "h2"))
    story.append(
        callout(
            "24-uurs tijd",
            "Een eindtijd vóór de starttijd wordt als volgende kalenderdag geïnterpreteerd. "
            "Voorbeeld: 18:30 → 01:00. Controleer op de detailpagina altijd beide volledige datums.",
            "safe",
        )
    )
    story.append(p("Annuleren", "h2"))
    story.append(
        bullets(
            [
                "Een beheerder kan een actieve shifttoewijzing annuleren zonder de historiek te verwijderen.",
                "Een volledige shiftannulatie beëindigt ook wachtende, bevestigde en reserve-toewijzingen.",
                "Verwijder een shift met registratiehistoriek nooit hard; gebruik annuleren.",
            ]
        )
    )
    story.append(PageBreak())

    add_section(
        story,
        "7",
        "Mailings",
        "AEFS schrijft e-mails eerst naar een databasewachtrij. De mailworker verstuurt ze later; "
        "de browser en het platformscherm hoeven dus niet open te blijven.",
    )
    story.append(p("Automatische mailflows", "h2"))
    story.append(
        bullets(
            [
                "publicatie van een evenement naar alle geschikte actieve leden;",
                "bevestiging of reservestatus van een eventinschrijving naar het betrokken lid;",
                "annulatie van een volledig evenement naar eventinschrijvers en bevestigde shiftvrijwilligers;",
                "persoonlijk overzicht van alle ingeplande shiften via de eventknop.",
            ]
        )
    )
    story.append(p("Manuele mailing", "h2"))
    story.append(
        process_table(
            [
                ("Doelgroep", "Kies alle leden, groepen, events of specifieke shiften."),
                ("Inhoud", "Schrijf onderwerp en bericht; controleer namen, data en links."),
                ("Bijlage", "Voeg optioneel één toegestane bijlage toe binnen de limiet."),
                ("Inplannen", "Bevestig pas na doelgroepcontrole; iedere ontvanger krijgt een afzonderlijke mail."),
                ("Opvolgen", "Bekijk status en fouten in Mailings; plan mislukte afleveringen gericht opnieuw in."),
            ]
        )
    )
    story.append(
        callout(
            "Alfa-mailveiligheid",
            "Laat de allowlist actief tot SMTP, afzender, links, workerplanning en quota op de "
            "live omgeving gecontroleerd zijn. Publiceer geen echt event als de uitnodigingen nog niet mogen vertrekken.",
            "warn",
        )
    )
    story.append(PageBreak())

    add_section(
        story,
        "8",
        "Rapporten en vergoedingen",
        "Rapporten gebruiken de werkelijk geregistreerde aanwezigheid. Controleer dus eerst de "
        "aanwezigheidsstatus op iedere shift.",
    )
    story.append(p("Aanwezigheidslijst per shift", "h2"))
    story.append(
        bullets(
            [
                "Kies de shift onder Rapporten of open het rapport vanuit de shiftdetailpagina.",
                "De lijst toont naam en voornaam en is leesbaar op een tablet.",
                "De checkbox schrijft rechtstreeks naar <b>aanwezig</b> en registreert het tijdstip.",
                "Vink een foutieve aanwezigheid opnieuw uit vóór je vergoedingen berekent.",
            ]
        )
    )
    story.append(p("Vergoedingsrapport per event", "h2"))
    story.append(
        process_table(
            [
                ("Event", "Kies het evenement waarvan aanwezigheid en vergoeding compleet zijn."),
                ("Groep", "Filter optioneel op één groep voor een afzonderlijk verenigingsrapport."),
                ("Kolommen", "Per gewerkte dag staat het verschuldigde bedrag; rechts staat het totaal."),
                ("Groepswerking", "Bij groeps-events wordt de ingestelde groepstoeslag per aanwezige shift toegevoegd."),
                ("Excel", "De export bevat dezelfde kolommen plus het ontsleutelde bankrekeningnummer."),
            ]
        )
    )
    story.append(
        callout(
            "Privacy bij export",
            "Excelbestanden met rekeningnummers zijn gevoelige betalingsbestanden. Bewaar ze beperkt, "
            "deel ze uitsluitend met de bevoegde ontvanger en verwijder lokale kopieën na verwerking.",
            "warn",
        )
    )
    story.append(PageBreak())

    add_section(
        story,
        "9",
        "Instellingen en alfa-checklist",
        "De instellingenpagina bundelt organisatiegegevens, mailnamen, standaardvergoedingen, "
        "groepstoeslag, groepswerking en shiftfuncties. Operationele status blijft alleen-lezen.",
    )
    story.append(p("Instellingen wijzigen", "h2"))
    story.append(
        bullets(
            [
                "Wijzig standaardbedragen vóór nieuwe events en shiften worden aangemaakt; bestaande bedragen blijven historisch behouden.",
                "Beheer shiftfuncties centraal. De verplichte standaardfunctie <b>Steward</b> blijft actief en kan niet worden hernoemd.",
                "Controleer mailconfiguratie, omgeving, tijdzone, PHP-versie en app-key-status.",
                "Wachtwoorden en servergeheimen horen nooit in de webinterface of repository.",
            ]
        )
    )
    story.append(p("Dagelijkse alfa-check", "h2"))
    checklist = [
        ("Dashboard", "Geen onverwachte wachtende of annulatie-items."),
        ("Events", "Status, data, shiften en capaciteiten kloppen."),
        ("Mailings", "Wachtrij verwerkt; fouten gericht opgevolgd."),
        ("Aanwezigheid", "Alle bevestigde vrijwilligers correct geregistreerd."),
        ("Rapporten", "Bedragen en groepsfilter steekproefsgewijs gecontroleerd."),
        ("Privacy", "Geen gevoelige exports of screenshots onbeheerd bewaard."),
    ]
    story.append(process_table(checklist))
    story.append(Spacer(1, 6 * mm))
    story.append(
        callout(
            "Bij twijfel",
            "Stop vóór een publicatie, bulkmail, annulatie of betaling. Noteer de exacte pagina, "
            "het tijdstip en de verwachte uitkomst en laat de situatie eerst technisch beoordelen.",
            "safe",
        )
    )

    doc.build(story, onFirstPage=cover_footer, onLaterPages=page_header)
    print(f"Adminhandleiding aangemaakt: {OUTPUT}")


if __name__ == "__main__":
    build_manual()
