FROM nginx:1.30.6

# zlib の脆弱性（CVE-2026-85091, CVE-2026-76844）対応 #142
# 公式イメージに Debian Security の修正版（deb13u1）が入るまでの暫定措置
RUN apt-get update \
    && apt-get install -y --no-install-recommends --only-upgrade zlib1g \
    && rm -rf /var/lib/apt/lists/*
