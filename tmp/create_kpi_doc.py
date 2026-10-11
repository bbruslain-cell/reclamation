from docx import Document
from docx.enum.section import WD_SECTION
from docx.enum.table import WD_CELL_VERTICAL_ALIGNMENT
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.oxml import OxmlElement
from docx.oxml.ns import qn
from docx.shared import Cm, Pt, RGBColor
from pathlib import Path


OUT = Path("output/documents/Explication_KPI_controle_interne.docx")


def set_cell_shading(cell, fill):
    tc_pr = cell._tc.get_or_add_tcPr()
    shd = tc_pr.find(qn("w:shd"))
    if shd is None:
        shd = OxmlElement("w:shd")
        tc_pr.append(shd)
    shd.set(qn("w:fill"), fill)


def set_cell_borders(cell, color="D9D9D9", size="6"):
    tc_pr = cell._tc.get_or_add_tcPr()
    borders = tc_pr.first_child_found_in("w:tcBorders")
    if borders is None:
        borders = OxmlElement("w:tcBorders")
        tc_pr.append(borders)
    for edge in ("top", "left", "bottom", "right", "insideH", "insideV"):
        tag = f"w:{edge}"
        element = borders.find(qn(tag))
        if element is None:
            element = OxmlElement(tag)
            borders.append(element)
        element.set(qn("w:val"), "single")
        element.set(qn("w:sz"), size)
        element.set(qn("w:space"), "0")
        element.set(qn("w:color"), color)


def set_cell_text(cell, text, bold=False, color="000000", align=None):
    cell.text = ""
    p = cell.paragraphs[0]
    if align is not None:
        p.alignment = align
    run = p.add_run(text)
    run.bold = bold
    run.font.name = "Aptos"
    run.font.size = Pt(9)
    run.font.color.rgb = RGBColor.from_string(color)
    cell.vertical_alignment = WD_CELL_VERTICAL_ALIGNMENT.CENTER


def set_table_style(table, header_fill="1F2A44"):
    table.autofit = False
    for row_idx, row in enumerate(table.rows):
        for cell in row.cells:
            set_cell_borders(cell)
            cell.margin_top = Cm(0.08)
            cell.margin_bottom = Cm(0.08)
            cell.margin_left = Cm(0.12)
            cell.margin_right = Cm(0.12)
            if row_idx == 0:
                set_cell_shading(cell, header_fill)
                for p in cell.paragraphs:
                    for run in p.runs:
                        run.font.color.rgb = RGBColor(255, 255, 255)
                        run.bold = True
            elif row_idx % 2 == 0:
                set_cell_shading(cell, "F6F8FB")


def add_heading(doc, text, level=1):
    p = doc.add_heading(text, level=level)
    for run in p.runs:
        run.font.name = "Aptos Display"
        run.font.color.rgb = RGBColor(0, 0, 0)
    return p


def add_paragraph(doc, text="", bold_lead=None):
    p = doc.add_paragraph()
    p.paragraph_format.space_after = Pt(7)
    p.paragraph_format.line_spacing = 1.08
    if bold_lead:
        lead = p.add_run(bold_lead)
        lead.bold = True
        lead.font.name = "Aptos"
        lead.font.size = Pt(10.5)
    run = p.add_run(text)
    run.font.name = "Aptos"
    run.font.size = Pt(10.5)
    return p


def add_bullet(doc, text):
    p = doc.add_paragraph(style="List Bullet")
    p.paragraph_format.space_after = Pt(4)
    run = p.add_run(text)
    run.font.name = "Aptos"
    run.font.size = Pt(10)
    return p


def add_formula(doc, formula):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(4)
    p.paragraph_format.space_after = Pt(8)
    run = p.add_run(formula)
    run.bold = True
    run.font.name = "Aptos"
    run.font.size = Pt(10.5)
    return p


def build_doc():
    doc = Document()
    section = doc.sections[0]
    section.top_margin = Cm(1.7)
    section.bottom_margin = Cm(1.6)
    section.left_margin = Cm(1.7)
    section.right_margin = Cm(1.7)

    styles = doc.styles
    styles["Normal"].font.name = "Aptos"
    styles["Normal"].font.size = Pt(10.5)
    styles["Title"].font.name = "Aptos Display"
    styles["Title"].font.size = Pt(20)
    styles["Title"].font.color.rgb = RGBColor(0, 0, 0)
    for style_name in ("Heading 1", "Heading 2", "Heading 3"):
        styles[style_name].font.name = "Aptos Display"
        styles[style_name].font.color.rgb = RGBColor(0, 0, 0)

    title = doc.add_paragraph(style="Title")
    title.alignment = WD_ALIGN_PARAGRAPH.LEFT
    title.add_run("Explication des KPI du controle interne")

    subtitle = doc.add_paragraph()
    subtitle.paragraph_format.space_after = Pt(14)
    r = subtitle.add_run("Tableau de bord des reclamations ANBG")
    r.font.name = "Aptos"
    r.font.size = Pt(12)
    r.font.color.rgb = RGBColor(80, 85, 105)

    add_paragraph(
        doc,
        "Ce document explique les indicateurs affiches dans le bloc Volume et activite du tableau de bord du controle interne. "
        "Il precise ce que chaque KPI mesure, comment il est calcule, comment l'interpreter et quelles limites garder en tete lors de la lecture des chiffres en production."
    )
    add_paragraph(
        doc,
        "L'exemple utilise les chiffres visibles sur l'ecran de production : 488 reclamations recues, 345 cloturees, 143 en attente, 108 en retard, 43,5 % dans les delais et 9,90 h de delai moyen de traitement."
    )

    add_heading(doc, "Synthese des KPI affiches", 1)
    table = doc.add_table(rows=1, cols=4)
    widths = [Cm(4.1), Cm(2.2), Cm(5.3), Cm(5.4)]
    for idx, width in enumerate(widths):
        table.columns[idx].width = width
    headers = ["KPI", "Valeur", "Ce que cela mesure", "Lecture rapide"]
    for i, h in enumerate(headers):
        set_cell_text(table.rows[0].cells[i], h, bold=True, color="FFFFFF", align=WD_ALIGN_PARAGRAPH.CENTER)
    rows = [
        ("Reclamations recues", "488", "Nombre total de reclamations enregistrees dans le perimetre filtre.", "Volume entrant global."),
        ("Cloturees", "345", "Nombre de dossiers termines avec le statut cloturee.", "Volume deja traite."),
        ("En attente", "143", "Nombre de dossiers non clotures.", "Stock restant a traiter."),
        ("En retard", "108", "Nombre de dossiers dont l'indicateur global de delai est en retard.", "Dossiers hors delai, ouverts ou deja termines."),
        ("Taux dans les delais", "43,5 %", "Part des dossiers clotures qui ont respecte le delai.", "Indicateur de conformite du traitement."),
        ("Delai moyen traitement", "9,90 h", "Moyenne des heures ouvrees de traitement sur les dossiers clotures.", "Vitesse moyenne de traitement des dossiers termines."),
    ]
    for row_data in rows:
        cells = table.add_row().cells
        for idx, value in enumerate(row_data):
            align = WD_ALIGN_PARAGRAPH.CENTER if idx == 1 else WD_ALIGN_PARAGRAPH.LEFT
            set_cell_text(cells[idx], value, align=align)
    set_table_style(table)

    add_heading(doc, "Regle generale de lecture", 1)
    add_paragraph(
        doc,
        "Les KPI sont toujours calcules apres application des filtres situes en haut du tableau de bord : periode, date de debut, date de fin, direction, service et application. "
        "Sur l'ecran de production analyse, les filtres sont sur Toute periode, Toutes les directions, Tous les services et Tous. Les chiffres representent donc le perimetre global visible par l'utilisateur."
    )
    add_paragraph(
        doc,
        "Il faut distinguer les indicateurs de volume et les indicateurs de delai. Les volumes comptent des dossiers. Les indicateurs de delai qualifient le respect ou le depassement des delais de traitement."
    )

    add_heading(doc, "Definition detaillee des indicateurs", 1)
    add_heading(doc, "Reclamations recues", 2)
    add_paragraph(
        doc,
        "Ce KPI compte toutes les reclamations creees dans l'application sur le perimetre filtre. Une demande est prise en compte des sa soumission par l'usager, meme si elle n'est pas encore affectee a un service."
    )
    add_formula(doc, "Reclamations recues = nombre total de demandes du perimetre")

    add_heading(doc, "Cloturees", 2)
    add_paragraph(
        doc,
        "Ce KPI compte les demandes dont le statut est cloturee. Une demande cloturee correspond a un dossier termine dans le workflow, generalement apres envoi ou finalisation de la reponse a l'usager."
    )
    add_formula(doc, "Cloturees = nombre de demandes avec statut cloturee")

    add_heading(doc, "En attente", 2)
    add_paragraph(
        doc,
        "Ce KPI correspond aux demandes non cloturees. Il ne designe pas un seul statut technique. Il peut regrouper des demandes nouvelles, affectees a un service, affectees a un agent ou avec une reponse preparee mais non finalisee."
    )
    add_formula(doc, "En attente = reclamations recues - cloturees")
    add_paragraph(doc, "Dans l'exemple de production :")
    add_formula(doc, "488 - 345 = 143 dossiers en attente")

    add_heading(doc, "En retard", 2)
    add_paragraph(
        doc,
        "Ce KPI compte les demandes dont l'indicateur global de delai est marque en retard. Il ne faut pas le confondre avec les dossiers en attente. Un dossier peut etre en retard tout en etant encore ouvert, ou avoir ete cloture apres le delai autorise."
    )
    add_formula(doc, "En retard = nombre de demandes avec delai_alerte egal a en_retard")
    add_paragraph(
        doc,
        "C'est un indicateur d'alerte pour le controle interne. Il sert a identifier les dossiers qui ont depasse le delai global attendu, puis a rechercher dans quelles directions ou services ces depassements se concentrent."
    )

    doc.add_page_break()
    add_heading(doc, "Calcul du taux dans les delais", 1)
    add_paragraph(
        doc,
        "Le taux dans les delais est calcule uniquement sur les dossiers clotures. Il ne se calcule pas sur toutes les reclamations recues. Les dossiers encore en attente ne sont donc pas inclus dans ce pourcentage."
    )
    add_formula(doc, "Taux dans les delais = dossiers clotures dans les delais / dossiers clotures x 100")

    add_paragraph(
        doc,
        "Dans l'application, un dossier entre dans les dossiers clotures dans les delais lorsqu'il remplit deux conditions :"
    )
    add_bullet(doc, "son statut est cloturee ;")
    add_bullet(doc, "son indicateur global de delai est dans_les_delais.")

    add_heading(doc, "Application a l'exemple de production", 2)
    add_paragraph(doc, "L'ecran affiche 345 dossiers clotures et un taux dans les delais de 43,5 %. Le nombre de dossiers clotures dans les delais peut donc etre retrouve ainsi :")
    add_formula(doc, "345 x 43,5 / 100 = 150,075")
    add_paragraph(
        doc,
        "On lit donc environ 150 dossiers clotures dans les delais et environ 195 dossiers clotures hors delai. Le calcul est arrondi dans l'interface, ce qui explique que le resultat exact puisse ne pas etre un entier parfait."
    )

    calc_table = doc.add_table(rows=1, cols=3)
    for idx, width in enumerate([Cm(6), Cm(4), Cm(7)]):
        calc_table.columns[idx].width = width
    for i, h in enumerate(["Element", "Valeur", "Interpretation"]):
        set_cell_text(calc_table.rows[0].cells[i], h, bold=True, color="FFFFFF", align=WD_ALIGN_PARAGRAPH.CENTER)
    for row_data in [
        ("Total cloturees", "345", "Denominateur du pourcentage."),
        ("Taux dans les delais", "43,5 %", "Part conforme parmi les dossiers termines."),
        ("Cloturees dans les delais", "environ 150", "Numerateur du pourcentage."),
        ("Cloturees hors delai", "environ 195", "Dossiers termines mais hors delai."),
    ]:
        cells = calc_table.add_row().cells
        for i, value in enumerate(row_data):
            set_cell_text(cells[i], value, align=WD_ALIGN_PARAGRAPH.CENTER if i == 1 else WD_ALIGN_PARAGRAPH.LEFT)
    set_table_style(calc_table)

    add_heading(doc, "Pourquoi le taux dans les delais ne correspond pas au KPI En retard", 1)
    add_paragraph(
        doc,
        "Le KPI Taux dans les delais regarde seulement les dossiers clotures. Le KPI En retard compte les dossiers dont l'indicateur global est en retard dans le perimetre, qu'ils soient encore ouverts ou deja clotures. Les deux indicateurs ne sont donc pas des complements directs."
    )

    compare = doc.add_table(rows=1, cols=3)
    for i, h in enumerate(["Indicateur", "Base de calcul", "Ce qu'il repond"]):
        set_cell_text(compare.rows[0].cells[i], h, bold=True, color="FFFFFF", align=WD_ALIGN_PARAGRAPH.CENTER)
    for row_data in [
        ("Taux dans les delais", "Dossiers clotures uniquement", "Parmi les dossiers termines, quelle part a respecte le delai ?"),
        ("En retard", "Tous les dossiers du perimetre avec alerte globale en retard", "Combien de dossiers sont ou ont ete hors delai ?"),
    ]:
        cells = compare.add_row().cells
        for i, value in enumerate(row_data):
            set_cell_text(cells[i], value)
    set_table_style(compare)

    add_paragraph(
        doc,
        "Ainsi, il est possible d'avoir 108 dossiers en retard et un taux dans les delais de 43,5 %. Cela signifie que l'indicateur de retard signale un volume de dossiers problematiques, tandis que le pourcentage mesure la conformite des dossiers deja finalises."
    )

    add_heading(doc, "Delai moyen de traitement", 1)
    add_paragraph(
        doc,
        "Le delai moyen de traitement est calcule sur les dossiers clotures. Il represente la moyenne des heures ouvrees de traitement, et non une duree calendaire brute. Les heures non ouvrees, les weekends ou jours feries peuvent etre exclus selon la configuration de delai active."
    )
    add_formula(doc, "Delai moyen = moyenne des heures ouvrees de cloture des dossiers clotures")
    add_paragraph(
        doc,
        "Dans l'exemple de production, 9,90 h signifie que les dossiers clotures ont pris en moyenne 9,90 heures ouvrees pour etre traites. Ce chiffre peut rester faible meme si le taux dans les delais est faible, car la moyenne peut etre influencee par beaucoup de dossiers traites rapidement et quelques dossiers tres en retard."
    )

    doc.add_page_break()
    add_heading(doc, "Comment presenter ces KPI en controle interne", 1)
    add_paragraph(doc, "Une lecture claire peut etre formulee ainsi :")
    add_bullet(doc, "488 reclamations ont ete recues sur le perimetre global.")
    add_bullet(doc, "345 dossiers sont clotures, soit environ 70,7 % du volume recu.")
    add_bullet(doc, "143 dossiers restent en attente de cloture.")
    add_bullet(doc, "108 dossiers sont signales en retard sur le delai global.")
    add_bullet(doc, "43,5 % des dossiers clotures ont ete traites dans les delais, soit environ 150 dossiers conformes sur 345 clotures.")
    add_bullet(doc, "Le delai moyen de traitement des dossiers clotures est de 9,90 heures ouvrees.")

    add_heading(doc, "Points d'attention", 1)
    attention = doc.add_table(rows=1, cols=2)
    set_cell_text(attention.rows[0].cells[0], "Point", bold=True, color="FFFFFF", align=WD_ALIGN_PARAGRAPH.CENTER)
    set_cell_text(attention.rows[0].cells[1], "Explication", bold=True, color="FFFFFF", align=WD_ALIGN_PARAGRAPH.CENTER)
    for row_data in [
        ("Ne pas calculer le taux sur 488", "Le taux dans les delais se calcule sur les dossiers clotures, donc sur 345 dans cet exemple."),
        ("Ne pas confondre retard et attente", "Un dossier en attente n'est pas automatiquement en retard. Il le devient si le delai global est depasse."),
        ("Analyser par direction et service", "Le chiffre global indique un probleme, mais l'action corrective depend des services qui concentrent les retards."),
        ("Tenir compte des filtres", "Changer la periode, la direction, le service ou l'application modifie tous les KPI."),
    ]:
        cells = attention.add_row().cells
        for i, value in enumerate(row_data):
            set_cell_text(cells[i], value)
    set_table_style(attention)

    add_heading(doc, "Formule de reference a retenir", 1)
    add_paragraph(
        doc,
        "La formule principale du tableau de bord est la suivante :"
    )
    add_formula(doc, "Taux dans les delais = demandes cloturees avec delai_alerte dans_les_delais / demandes cloturees x 100")
    add_paragraph(
        doc,
        "Dans l'exemple de production, ce calcul donne environ 150 dossiers clotures dans les delais sur 345 dossiers clotures, soit 43,5 %."
    )

    OUT.parent.mkdir(parents=True, exist_ok=True)
    doc.save(OUT)


if __name__ == "__main__":
    build_doc()
    print(OUT.resolve())
