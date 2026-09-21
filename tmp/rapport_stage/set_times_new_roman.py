from __future__ import annotations

from pathlib import Path
from tempfile import NamedTemporaryFile
from zipfile import ZipFile
import os

from lxml import etree


docx_path = Path(r"C:\Suivi-reclamation\output\documents\Rapport_stage_ANBG_IITG_adapte.docx")
ns = {"w": "http://schemas.openxmlformats.org/wordprocessingml/2006/main"}
W = "{" + ns["w"] + "}"
font_name = "Times New Roman"


def set_font(run_properties):
    fonts = run_properties.find(W + "rFonts")
    if fonts is None:
        fonts = etree.Element(W + "rFonts")
        run_properties.insert(0, fonts)
    for attr in ("ascii", "hAnsi", "eastAsia", "cs"):
        fonts.set(W + attr, font_name)
    for attr in ("asciiTheme", "hAnsiTheme", "eastAsiaTheme", "cstheme"):
        fonts.attrib.pop(W + attr, None)


with NamedTemporaryFile(prefix="rapport_tnr_", suffix=".docx", dir=docx_path.parent, delete=False) as tmp:
    temp_path = Path(tmp.name)

try:
    with ZipFile(docx_path) as source, ZipFile(temp_path, "w") as target:
        for item in source.infolist():
            data = source.read(item.filename)
            if item.filename.startswith("word/") and item.filename.endswith(".xml"):
                root = etree.fromstring(data)
                if root.nsmap.get("w") == ns["w"] or ns["w"] in root.nsmap.values():
                    # Styles, document defaults, headers, footers, tables,
                    # field results and any editable text boxes all inherit TNR.
                    for rpr in root.xpath(".//w:rPr", namespaces=ns):
                        set_font(rpr)
                    for run in root.xpath(".//w:r", namespaces=ns):
                        rpr = run.find(W + "rPr")
                        if rpr is None:
                            rpr = etree.Element(W + "rPr")
                            run.insert(0, rpr)
                        set_font(rpr)
                    if item.filename == "word/document.xml":
                        for paragraph in root.xpath(".//w:p[w:pPr/w:shd[@w:fill='000000']]", namespaces=ns):
                            properties = paragraph.find(W + "pPr")
                            shading = properties.find(W + "shd")
                            shading.set(W + "fill", "F2F2F2")
                            shading.attrib.pop(W + "themeFill", None)
                            justification = properties.find(W + "jc")
                            if justification is None:
                                justification = etree.SubElement(properties, W + "jc")
                            justification.set(W + "val", "left")
                            spacing = properties.find(W + "spacing")
                            if spacing is not None:
                                spacing.set(W + "line", "240")
                                spacing.set(W + "lineRule", "auto")
                    data = etree.tostring(root, xml_declaration=True, encoding="UTF-8", standalone=True)
            target.writestr(item, data)
    os.replace(temp_path, docx_path)
except Exception:
    temp_path.unlink(missing_ok=True)
    raise

print(docx_path)
