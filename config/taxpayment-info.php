<?php
return [

    "fnc_create_taxpaymentstatus"=>"
        DROP FUNCTION IF EXISTS taxpayment_info.fnc_taxpaymentstatus();
        CREATE OR REPLACE FUNCTION taxpayment_info.fnc_taxpaymentstatus()
        Returns Boolean
        LANGUAGE plpgsql AS $$
        BEGIN
            DROP TABLE IF EXISTS taxpayment_info.tax_payment_status CASCADE;
                
            CREATE TABLE taxpayment_info.tax_payment_status AS
            -- Owner PII stays only in the encrypted source table. The status
            -- table is published to map services and must remain non-PII.
            SELECT b.tax_code, b.bin as bin, b.ward, b.building_associated_to, t.last_payment_date,
                CASE 
                    WHEN t.last_payment_date='1970-01-01' THEN 99    
                    WHEN t.last_payment_date is not NULL THEN 
                        CASE
                            WHEN date_part('year', AGE(CURRENT_DATE, t.last_payment_date::date))::int > 5 THEN 5
                            ELSE date_part('year', AGE(CURRENT_DATE, t.last_payment_date::date))::int
                        END
                END as due_year, 
                b.geom, Now() as created_at, Now() as updated_at, b.deleted_at
            FROM building_info.buildings b 
            LEFT join taxpayment_info.tax_payments t 
            ON b.tax_code=t.tax_code
            WHERE b.deleted_at IS NULL
            -- AND b.tax_code IS NOT NULL
            ;
           
            Return True
        ;
        END
        $$;
    ",

    "fnc_updonimprt_gridnward_tax"=>"DROP FUNCTION IF EXISTS taxpayment_info.fnc_updonimprt_gridnward_tax();    
    CREATE OR REPLACE FUNCTION taxpayment_info.fnc_updonimprt_gridnward_tax()
        Returns Boolean
        LANGUAGE plpgsql AS $$
        BEGIN
            UPDATE layer_info.wards SET bldgtaxpdprprtn = 0;
            UPDATE layer_info.wards w 
            SET bldgtaxpdprprtn = q.percentage_proportion
            FROM (
                    SELECT a.ward,  a.count, b.totalcount,
                    ROUND(a.count * 100/b.totalcount::numeric, 2 ) as percentage_proportion
                FROM ( 
                    select ward, count(*) as count
                    FROM taxpayment_info.tax_payment_status  
                    WHERE due_year = 0 
                        AND building_associated_to is NULL 
                        AND deleted_at is NULL 
                    GROUP BY ward
                ) a
                JOIN ( 
                    select ward, count(*) as totalcount
                    FROM taxpayment_info.tax_payment_status 
                    WHERE building_associated_to is NULL 
                        AND deleted_at is NULL 
                    GROUP BY ward
                ) b ON b.ward = a.ward
                ORDER BY a.ward asc
            ) as q
            WHERE w.ward = q.ward;
                
            UPDATE layer_info.grids SET bldgtaxpdprprtn = 0;
            UPDATE layer_info.grids g
            SET bldgtaxpdprprtn = q.percentage_proportion
            FROM (
                SELECT a.id,  a.count, b.totalcount,
                    ROUND(a.count * 100/b.totalcount::numeric, 2 ) as percentage_proportion
                FROM ( 
                    SELECT g.id, count(t.bin) as count
                    FROM taxpayment_info.tax_payment_status t, layer_info.grids g
                    WHERE ST_Contains(ST_Transform(g.geom, 4326), t.geom)
                        AND t.due_year = 0 
                        AND t.building_associated_to is NULL 
                        AND t.deleted_at is NULL 
                    GROUP BY g.id
                ) a
                JOIN ( 
                    SELECT  g.id, count(t.bin) as totalcount
                    FROM taxpayment_info.tax_payment_status t, layer_info.grids g
                    WHERE ST_Contains(ST_Transform(g.geom, 4326), t.geom)
                        AND t.building_associated_to is NULL 
                        AND t.deleted_at is NULL 
                    GROUP BY g.id
                ) b ON b.id = a.id
                ORDER BY a.id asc
            )as q
            WHERE g.id = q.id;
                
        Return True
    ;
    END
    $$;"

];
