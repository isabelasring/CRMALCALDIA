#!/usr/bin/env python3
"""Rellena FormatoSolicitud.doc conservando todo el diseño de la plantilla."""

import json
import os
import re
import shutil
import subprocess
import sys
import tempfile
import time

import uno


def get_lo_profile():
    profile = os.environ.get("LO_PROFILE")
    if not profile:
        profile = os.path.join(tempfile.gettempdir(), f"lo-profile-{os.getuid()}")
    os.makedirs(profile, exist_ok=True)
    return profile


def start_soffice():
    profile = get_lo_profile()
    profile_url = "file://" + profile.replace(" ", "%20")
    port = 20000 + (os.getpid() % 10000)
    proc = subprocess.Popen(
        [
            "soffice",
            "--headless",
            "--invisible",
            "--nologo",
            "--nofirststartwizard",
            f"-env:UserInstallation={profile_url}",
            f"--accept=socket,host=127.0.0.1,port={port};urp;",
        ],
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
        env={
            **os.environ,
            "HOME": profile,
            "TMPDIR": profile,
        },
    )
    time.sleep(1.5)
    return proc, port


def connect(port):
    localContext = uno.getComponentContext()
    resolver = localContext.ServiceManager.createInstanceWithContext(
        "com.sun.star.bridge.UnoUrlResolver", localContext
    )
    endpoint = f"uno:socket,host=127.0.0.1,port={port};urp;StarOffice.ComponentContext"
    for _ in range(50):
        try:
            return resolver.resolve(endpoint)
        except Exception:
            time.sleep(0.2)
    raise RuntimeError("No se pudo conectar a LibreOffice")


def get_tables(doc):
    tables = []
    enum = doc.getText().createEnumeration()
    while enum.hasMoreElements():
        el = enum.nextElement()
        if "TextTable" in el.getImplementationName():
            tables.append(el)
    return tables


def replace_regex(doc, pattern, replacement):
    sd = doc.createSearchDescriptor()
    sd.SearchRegularExpression = True
    sd.SearchString = pattern
    sd.ReplaceString = replacement
    doc.replaceAll(sd)


def replace_label_underscores(doc, label, value):
    text = (value or "").strip()
    if not text:
        return
    pattern = re.escape(label) + r"  _+"
    replace_regex(doc, pattern, label + "  " + text)


def replace_label_underscores_single(doc, label, value):
    text = (value or "").strip()
    if not text:
        return
    pattern = re.escape(label) + r" _+"
    replace_regex(doc, pattern, label + " " + text)


def mark_checkbox_option(doc, option_label):
    """Marca X en la opción sin borrar las demás del formato."""
    sd = doc.createSearchDescriptor()
    sd.SearchString = option_label + "  X"
    if doc.findFirst(sd):
        return

    sd = doc.createSearchDescriptor()
    sd.SearchString = option_label
    sd.ReplaceString = option_label + "  X"
    doc.replaceAll(sd)


def separator_after_label(prefix):
    """Conserva ': ' o espacio que trae la plantilla; si no hay, agrega ': '."""
    if prefix.endswith(": "):
        return ""
    if prefix.endswith(" "):
        return ""
    if prefix.rstrip().endswith(":"):
        return " "
    return ": "


def fill_cell_after_label(cell, label, value):
    """Escribe el valor después de la etiqueta sin borrar el resto de la celda."""
    text = (value or "").strip()
    if not text:
        return

    current = cell.getString()
    if not current.strip():
        cell.setString(label.rstrip() + separator_after_label(label.rstrip()) + text)
        return

    for candidate in (label, label.strip()):
        if candidate and candidate in current:
            idx = current.index(candidate)
            prefix = current[: idx + len(candidate)]
            suffix = current[idx + len(candidate) :]
            suffix = re.sub(r"^[\s_:]+", "", suffix)
            joiner = separator_after_label(prefix)
            new_text = prefix + joiner + text
            if suffix.strip():
                new_text += " " + suffix.strip()
            cell.setString(new_text)
            return

    cell.setString(current.rstrip() + " " + text)


def fill_doc(template_path, output_path, data):
    proc, port = start_soffice()

    try:
        shutil.copy2(template_path, output_path)
        ctx = connect(port)
        desktop = ctx.ServiceManager.createInstanceWithContext(
            "com.sun.star.frame.Desktop", ctx
        )
        url = uno.systemPathToFileUrl(os.path.abspath(output_path))
        doc = desktop.loadComponentFromURL(url, "_blank", 0, ())

        tables = get_tables(doc)
        if len(tables) < 5:
            raise RuntimeError("Plantilla FormatoSolicitud.doc: estructura de tablas inesperada")

        fill_cell_after_label(tables[0].getCellByPosition(0, 0), "FECHA: ", data.get("fecha", ""))
        fill_cell_after_label(
            tables[0].getCellByPosition(1, 0), "RADICADO Nº   ", data.get("radicado", "")
        )

        fill_cell_after_label(
            tables[1].getCellByPosition(0, 0), "PETICIONARIO: ", data.get("peticionario", "")
        )
        fill_cell_after_label(tables[1].getCellByPosition(1, 0), "CEDULA ", data.get("cedula", ""))
        fill_cell_after_label(
            tables[1].getCellByPosition(0, 1), "DIRECCIÓN: ", data.get("direccion", "")
        )
        fill_cell_after_label(
            tables[1].getCellByPosition(0, 2), "TELEFONO", data.get("telefono", "")
        )
        fill_cell_after_label(tables[1].getCellByPosition(1, 2), "BARRIO", data.get("barrio", ""))
        fill_cell_after_label(
            tables[1].getCellByPosition(0, 3), "CORREO ELECTRÓNICO", data.get("correo", "")
        )

        if data.get("aceptaCorreo"):
            correo_cell = tables[1].getCellByPosition(0, 4)
            current = correo_cell.getString()
            if current and " X" not in current and not current.rstrip().endswith("X"):
                correo_cell.setString(current.rstrip() + "  X")

        fill_cell_after_label(
            tables[2].getCellByPosition(0, 0), "PERJUDICANTE: ", data.get("perjudicante", "")
        )
        fill_cell_after_label(tables[2].getCellByPosition(1, 0), "TEL: ", data.get("telPerjudicante", ""))
        fill_cell_after_label(
            tables[2].getCellByPosition(0, 1), "DIRECCIÓN: ", data.get("direccionPerjudicante", "")
        )
        fill_cell_after_label(
            tables[2].getCellByPosition(1, 1), "BARRIO: ", data.get("barrioPerjudicante", "")
        )

        canal = (data.get("canalDeReporte") or "").strip()
        if canal == "Telefono":
            mark_checkbox_option(doc, "ATENCIÓN TELEFONICA")
        elif canal == "Personal":
            mark_checkbox_option(doc, "ATENCIÓN PERSONAL")
        elif canal == "Correo" and data.get("correo"):
            correo_cell = tables[1].getCellByPosition(0, 3)
            current = correo_cell.getString()
            if current and " X" not in current and not current.rstrip().endswith("X"):
                correo_cell.setString(current.rstrip() + "  X")

        replace_label_underscores(doc, "DESCRIPCION QUEJA:", data.get("descripcion", ""))
        replace_label_underscores(doc, "RESPUESTA INMEDIATA:", data.get("respuestaInmediata", ""))
        replace_label_underscores_single(doc, "RECIBIDA POR:", data.get("recibidaPor", ""))
        replace_label_underscores_single(doc, "REMITIDO A:", data.get("remitidoA", ""))

        props = (uno.createUnoStruct("com.sun.star.beans.PropertyValue"),)
        props[0].Name = "FilterName"
        props[0].Value = "MS Word 97"
        doc.storeToURL(url, tuple(props))
        doc.close(True)
    finally:
        proc.terminate()
        proc.wait()


def convert_to_pdf(doc_path, pdf_path):
    profile = get_lo_profile()
    profile_url = "file://" + profile.replace(" ", "%20")
    subprocess.run(
        [
            "soffice",
            "--headless",
            "--invisible",
            "--nologo",
            "--nofirststartwizard",
            f"-env:UserInstallation={profile_url}",
            "--convert-to",
            "pdf:writer_pdf_Export",
            "--outdir",
            os.path.dirname(pdf_path) or ".",
            doc_path,
        ],
        check=True,
        stdout=subprocess.DEVNULL,
        stderr=subprocess.DEVNULL,
        env={
            **os.environ,
            "HOME": profile,
            "TMPDIR": profile,
        },
    )
    generated = os.path.join(
        os.path.dirname(pdf_path) or ".",
        os.path.splitext(os.path.basename(doc_path))[0] + ".pdf",
    )
    if generated != pdf_path and os.path.exists(generated):
        shutil.move(generated, pdf_path)


def main():
    if len(sys.argv) < 3:
        print(
            "Uso: fill-formato-solicitud.py <plantilla.doc> <salida> [doc|pdf]",
            file=sys.stderr,
        )
        sys.exit(1)

    template_path = sys.argv[1]
    output_path = sys.argv[2]
    output_format = (sys.argv[3] if len(sys.argv) > 3 else "doc").lower()
    payload = json.load(sys.stdin)

    doc_out = output_path
    if output_format == "pdf":
        doc_out = output_path + ".doc.tmp"

    fill_doc(template_path, doc_out, payload)

    if output_format == "pdf":
        convert_to_pdf(doc_out, output_path)
        os.remove(doc_out)

    print(output_path)


if __name__ == "__main__":
    main()
