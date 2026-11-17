FROM maven:3.9-eclipse-temurin-25 AS build
WORKDIR /workspace
COPY pom.xml ./
RUN mvn -B -ntp -DskipTests dependency:go-offline
COPY src src
RUN mvn -B -ntp -DskipTests package

FROM eclipse-temurin:25-jre
WORKDIR /work
RUN apt-get update && apt-get install -y --no-install-recommends wget \
    && rm -rf /var/lib/apt/lists/*
COPY --from=build /workspace/target/quarkus-app/ /work/
RUN mkdir -p /work/logs && chown -R 10001:10001 /work
USER 10001
EXPOSE 8080
ENV QUARKUS_HTTP_HOST=0.0.0.0
ENTRYPOINT ["java", "-Djava.util.logging.manager=org.jboss.logmanager.LogManager", "-jar", "/work/quarkus-run.jar"]
