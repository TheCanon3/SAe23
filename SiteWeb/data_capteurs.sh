#!/bin/bash

# --- Configuration ---
MQTT_TOPICS=(
    "AM107/by-room/B106/data"
    "AM107/by-room/B101/data"
    "AM107/by-room/E105/data"
    "AM107/by-room/E100/data"
)

MQTT_BROKER="mqtt.iut-blagnac.fr"
MQTT_PORT="1883"

DB_USER="root"
DB_PASS="passroot"
DB_NAME="SAe23"

MYSQL_CLIENT="/opt/lampp/bin/mysql"

# --- Start ---
echo "Starting MQTT subscription for sensor data collection..."
echo "Connecting to $MQTT_BROKER:$MQTT_PORT"

for TOPIC in "${MQTT_TOPICS[@]}"; do
    mosquitto_sub -h "$MQTT_BROKER" -p "$MQTT_PORT" -t "$TOPIC" | while read -r PAYLOAD; do

        TEMP=$(echo "$PAYLOAD" | jq '.[0].temperature')
        ILLU=$(echo "$PAYLOAD" | jq '.[0].illumination')
        CO2=$(echo "$PAYLOAD" | jq '.[0].co2')
        ROOM=$(echo "$PAYLOAD" | jq -r '.[1].room')


	echo "DEBUG Payload: $PAYLOAD"
	echo "Parsed room: $ROOM"
	echo "Temp: $TEMP | Illu: $ILLU | CO2: $CO2"


        DATE=$(date "+%Y-%m-%d")
        TIME=$(date "+%H:%M:%S")

        declare -A SENSORS=(
            ["temperature"]="$TEMP"
            ["illumination"]="$ILLU"
            ["co2"]="$CO2"
        )
        declare -A UNITS=(
            ["temperature"]="C"
            ["illumination"]="lux"
            ["co2"]="ppm"
        )

        echo "Message received for room '$ROOM' at $DATE $TIME"

        # Placeholder values (should be pre-filled in production)
        SALLE_CAPACITE=0
        SALLE_TYPE="Undefined"
        SALLE_ID_BATIMENT=1

        if [[ -n "$ROOM" && "$ROOM" != "null" ]]; then
            "$MYSQL_CLIENT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
                INSERT IGNORE INTO salles (nom_salle, type, capacite, id_batiment)
                VALUES ('$ROOM', '$SALLE_TYPE', $SALLE_CAPACITE, $SALLE_ID_BATIMENT);
            "
            if [ $? -eq 0 ]; then
                echo "  Room '$ROOM' processed (inserted if new, with default data)."
            else
                echo "  Error processing room '$ROOM'. Check if building ID $SALLE_ID_BATIMENT exists."
            fi
        else
            echo "  Warning: Room name is missing or null. Skipping entry."
            continue
        fi

        for TYPE in "${!SENSORS[@]}"; do
            SENSOR_NAME="${TYPE}_${ROOM}"
            VALUE="${SENSORS[$TYPE]}"
            UNIT="${UNITS[$TYPE]}"

            if [[ -z "$VALUE" || "$VALUE" == "null" ]]; then
                echo "  Warning: '$TYPE' value for room '$ROOM' is missing. Skipping."
                continue
            fi

            "$MYSQL_CLIENT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
                INSERT IGNORE INTO capteurs (nom_capteur, type, unite, nom_salle)
                VALUES ('$SENSOR_NAME', '$TYPE', '$UNIT', '$ROOM');
            "
            if [ $? -eq 0 ]; then
                echo "  Sensor '$SENSOR_NAME' (Type: $TYPE, Room: $ROOM) processed."
            else
                echo "  Error inserting sensor '$SENSOR_NAME'. Check 'nom_salle' foreign key."
            fi

            # Insert the measurement into the database
            "$MYSQL_CLIENT" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -e "
                INSERT INTO mesures (nom_capteur, valeur, date, horaire)
                VALUES ('$SENSOR_NAME', '$VALUE', '$DATE', '$TIME');
            "
            if [ $? -eq 0 ]; then
                echo "  Measurement inserted for '$SENSOR_NAME': Value $VALUE at $DATE $TIME."
            else
                echo "  Error inserting measurement for '$SENSOR_NAME'."
            fi
        done
        echo "---"
    done &
done

echo "All MQTT subscriptions are active. Press Ctrl+C to stop the script."
wait
