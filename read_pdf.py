import sys
try:
    import PyPDF2
    
    pdf_path = r'c:\Users\IT Admin\Downloads\Otomasyon\2000.01.01_ Divan Toplantisi_ BOS FORM.pdf'
    
    with open(pdf_path, 'rb') as file:
        pdf_reader = PyPDF2.PdfReader(file)
        print(f"Total pages: {len(pdf_reader.pages)}\n")
        
        for page_num, page in enumerate(pdf_reader.pages, 1):
            print(f"\n{'='*60}")
            print(f"PAGE {page_num}")
            print(f"{'='*60}\n")
            text = page.extract_text()
            print(text)
            
except ImportError:
    print("PyPDF2 not installed. Trying pdfplumber...")
    try:
        import pdfplumber
        
        pdf_path = r'c:\Users\IT Admin\Downloads\Otomasyon\2000.01.01_ Divan Toplantisi_ BOS FORM.pdf'
        
        with pdfplumber.open(pdf_path) as pdf:
            print(f"Total pages: {len(pdf.pages)}\n")
            
            for page_num, page in enumerate(pdf.pages, 1):
                print(f"\n{'='*60}")
                print(f"PAGE {page_num}")
                print(f"{'='*60}\n")
                text = page.extract_text()
                print(text)
    except ImportError:
        print("Neither PyPDF2 nor pdfplumber is installed.")
        print("Please install one: pip install PyPDF2 or pip install pdfplumber")
except Exception as e:
    print(f"Error: {e}")
    import traceback
    traceback.print_exc()
