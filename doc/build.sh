latex -output-directory=$1 -output-format=pdf $1/main.tex
latex -output-directory=$1 -output-format=pdf $1/main.tex
latex2html -split=0 -toc_depth=10 -nonavigation -info 0 -lcase_tags -noaddress -nosubdir $1/main.tex
